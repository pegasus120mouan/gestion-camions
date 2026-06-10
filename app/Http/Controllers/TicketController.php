<?php

namespace App\Http\Controllers;

use App\Models\FicheSortie;
use App\Models\Groupe;
use App\Models\GroupeAgent;
use App\Models\GroupeVehicule;
use App\Models\Parc;
use App\Models\ParticulierAgent;
use App\Models\ParticulierAgentPrix;
use App\Models\ParticulierGroupe;
use App\Models\PontEtat;
use App\Models\PrixAgent;
use App\Models\Produit;
use App\Models\Stock;
use App\Models\Ticket;
use App\Services\UsinesParProduitService;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class TicketController extends Controller
{
    private function prixUnitairePrixAgent(int $idAgentApi, int $idUsine, ?int $produitId, ?string $dateTicket): ?float
    {
        if ($idAgentApi <= 0 || $idUsine <= 0) {
            return null;
        }

        $date = $dateTicket ? Carbon::parse($dateTicket)->startOfDay() : now()->startOfDay();

        $query = PrixAgent::where('id_agent', $idAgentApi)
            ->where('id_usine', $idUsine)
            ->where(fn ($q) => $q->whereNull('date_debut')->orWhereDate('date_debut', '<=', $date))
            ->where(fn ($q) => $q->whereNull('date_fin')->orWhereDate('date_fin', '>=', $date));

        if ($produitId) {
            $query->where('produit_id', $produitId);
        }

        $match = $query->orderByDesc('date_debut')->first();

        return $match ? (float) $match->prix : null;
    }

    private function prixUnitaireParticulierAgent($prixRecords, int $particulierAgentId, int $idUsine, ?string $dateTicket): ?float
    {
        if ($particulierAgentId <= 0 || $idUsine <= 0) {
            return null;
        }

        $date = $dateTicket
            ? Carbon::parse($dateTicket)->startOfDay()
            : now()->startOfDay();

        $match = $prixRecords
            ->where('particulier_agent_id', $particulierAgentId)
            ->where('id_usine', $idUsine)
            ->filter(function (ParticulierAgentPrix $prix) use ($date) {
                if ($prix->date_debut && $prix->date_debut->gt($date)) {
                    return false;
                }
                if ($prix->date_fin && $prix->date_fin->lt($date)) {
                    return false;
                }

                return true;
            })
            ->sortByDesc(fn (ParticulierAgentPrix $prix) => $prix->date_debut ?? $prix->created_at)
            ->first();

        return $match ? (float) $match->prix : null;
    }

    public function index(Request $request)
    {
        $vehicule = trim((string) $request->query('vehicule', ''));
        $usine = trim((string) $request->query('usine', ''));
        $agent = trim((string) $request->query('agent', ''));

        $query = Ticket::query()->orderBy('date_ticket', 'desc');

        if ($vehicule !== '') {
            $query->where('matricule_vehicule', 'like', '%' . $vehicule . '%');
        }
        if ($usine !== '') {
            $query->where('id_usine', $usine);
        }
        if ($agent !== '') {
            $query->where('id_agent', $agent);
        }

        $ticketsPaginated = $query->with('particulierAgent.groupe')->paginate(20)->withQueryString();

        // Récupérer les usines et agents depuis l'API pour les noms
        $timeout = 10;
        $usinesApi = [];
        $agentsApi = [];

        try {
            $usinesUrl = (string) config('services.external_auth.mes_usines_url', 'https://api.objetombrepegasus.online/api/camions/mes_usines.php');
            $pageU = 1;
            $hasMoreU = true;
            while ($hasMoreU) {
                $usinesResponse = Http::acceptJson()
                    ->withoutVerifying()
                    ->timeout($timeout)
                    ->get($usinesUrl, ['page' => $pageU]);
                if ($usinesResponse->successful()) {
                    $pageUsines = $usinesResponse->json('usines') ?? [];
                    if (empty($pageUsines)) {
                        $hasMoreU = false;
                    } else {
                        $usinesApi = array_merge($usinesApi, $pageUsines);
                        $paginationU = $usinesResponse->json('pagination');
                        $currentPageU = $paginationU['current_page'] ?? $pageU;
                        $lastPageU = $paginationU['last_page'] ?? 1;
                        if ($currentPageU >= $lastPageU) {
                            $hasMoreU = false;
                        } else {
                            $pageU++;
                        }
                    }
                } else {
                    $hasMoreU = false;
                }
            }
        } catch (\Throwable $e) {}

        try {
            $page = 1;
            $hasMore = true;
            while ($hasMore) {
                $agentsResponse = Http::acceptJson()
                    ->withoutVerifying()
                    ->timeout($timeout)
                    ->get('https://api.objetombrepegasus.online/api/camions/mes_agents.php', ['page' => $page]);
                if ($agentsResponse->successful()) {
                    $pageAgents = $agentsResponse->json('agents') ?? [];
                    if (empty($pageAgents)) {
                        $hasMore = false;
                    } else {
                        $agentsApi = array_merge($agentsApi, $pageAgents);
                        $pagination = $agentsResponse->json('pagination');
                        $currentPage = $pagination['current_page'] ?? $page;
                        $lastPage = $pagination['last_page'] ?? 1;
                        if ($currentPage >= $lastPage) {
                            $hasMore = false;
                        } else {
                            $page++;
                        }
                    }
                } else {
                    $hasMore = false;
                }
            }
        } catch (\Throwable $e) {}

        // Indexer par ID (API d'abord, puis usines locales en complément)
        $usinesById = [];
        foreach ($usinesApi as $u) {
            $usinesById[$u['id_usine'] ?? 0] = $u['nom_usine'] ?? '';
        }
        foreach (\App\Models\Usine::all() as $ul) {
            if (!isset($usinesById[$ul->id_usine])) {
                $usinesById[$ul->id_usine] = $ul->nom_usine;
            }
        }
        $agentsById = [];
        foreach ($agentsApi as $a) {
            $agentsById[$a['id_agent'] ?? 0] = $a['nom_complet'] ?? '';
        }

        // Convertir en tableau pour compatibilité avec la vue existante
        $tickets = $ticketsPaginated->items();
        $particulierAgentIds = collect($tickets)
            ->pluck('particulier_agent_id')
            ->filter()
            ->unique()
            ->values();
        $prixParticuliers = $particulierAgentIds->isNotEmpty()
            ? ParticulierAgentPrix::whereIn('particulier_agent_id', $particulierAgentIds)->get()
            : collect();

        $ticketsArray = [];
        foreach ($tickets as $ticket) {
            $prixUnitaireAgent = null;
            $montantCalcule = null;

            if ($ticket->particulier_agent_id && $ticket->id_usine) {
                $prixUnitaireAgent = $this->prixUnitaireParticulierAgent(
                    $prixParticuliers,
                    (int) $ticket->particulier_agent_id,
                    (int) $ticket->id_usine,
                    $ticket->date_ticket?->format('Y-m-d')
                );

                // Fallback : chercher dans prix_agents via l'id_agent API
                if ($prixUnitaireAgent === null) {
                    $idAgentApi = (int) ($ticket->particulierAgent?->id_agent ?? 0);
                    if ($idAgentApi > 0) {
                        $prixUnitaireAgent = $this->prixUnitairePrixAgent(
                            $idAgentApi,
                            (int) $ticket->id_usine,
                            null,
                            $ticket->date_ticket?->format('Y-m-d')
                        );
                    }
                }

                if ($prixUnitaireAgent !== null && (float) $ticket->poids > 0) {
                    $montantCalcule = $prixUnitaireAgent * (float) $ticket->poids;
                }
            }

            $ticketsArray[] = [
                'id_ticket' => $ticket->id_ticket,
                'numero_ticket' => $ticket->numero_ticket,
                'date_ticket' => $ticket->date_ticket ? $ticket->date_ticket->format('Y-m-d') : null,
                'matricule_vehicule' => $ticket->matricule_vehicule,
                'vehicule_id' => $ticket->vehicule_id,
                'poids' => $ticket->poids,
                'id_usine' => $ticket->id_usine,
                'nom_usine' => $usinesById[$ticket->id_usine] ?? '-',
                'id_agent' => $ticket->id_agent,
                'nom_agent' => $ticket->particulierAgent
                    ? $ticket->particulierAgent->nom_complet
                    : ($agentsById[$ticket->id_agent] ?? '-'),
                'nom_groupe' => $ticket->particulierAgent?->groupe?->nom_groupe ?? '-',
                'particulier_agent_id' => $ticket->particulier_agent_id,
                'prix_unitaire' => $ticket->prix_unitaire,
                'prix_unitaire_agent' => $prixUnitaireAgent,
                'montant_calcule' => $montantCalcule,
                'montant_paie' => $ticket->montant_paie,
                'statut_ticket' => $ticket->statut_ticket,
                'created_at' => $ticket->created_at ? $ticket->created_at->format('Y-m-d H:i:s') : null,
                'conformite' => $ticket->conformite,
            ];
        }

        $pagination = [
            'current_page' => $ticketsPaginated->currentPage(),
            'per_page' => $ticketsPaginated->perPage(),
            'total' => $ticketsPaginated->total(),
            'last_page' => $ticketsPaginated->lastPage(),
        ];

        // Récupérer la liste des véhicules pour l'autocomplétion
        $vehicules = Ticket::distinct()->pluck('matricule_vehicule')->filter()->toArray();

        // Récupérer les fiches de sortie associées aux tickets
        $ticketIds = array_column($ticketsArray, 'id_ticket');
        $fichesSortie = [];
        if (!empty($ticketIds)) {
            $fiches = FicheSortie::whereIn('id_ticket', $ticketIds)->get()->keyBy('id_ticket');
            foreach ($fiches as $idTicket => $fiche) {
                $fichesSortie[$idTicket] = [
                    'fiche_id' => $fiche->id,
                    'origine' => $fiche->nom_pont,
                    'date_chargement' => $fiche->date_chargement ? $fiche->date_chargement->format('d-m-Y') : '',
                    'poids_parc' => $fiche->poids_pont,
                    'prix_unitaire_transport' => $fiche->prix_unitaire_transport,
                    'poids_unitaire_regime' => $fiche->poids_unitaire_regime,
                    'nom_produit' => $fiche->nom_produit,
                ];
            }
        }

        // Ajouter les infos de fiche de sortie à chaque ticket
        foreach ($ticketsArray as &$ticket) {
            $idTicket = $ticket['id_ticket'] ?? null;
            if ($idTicket && isset($fichesSortie[$idTicket])) {
                $ticket['fiche_id'] = $fichesSortie[$idTicket]['fiche_id'];
                $ticket['origine'] = $fichesSortie[$idTicket]['origine'];
                $ticket['date_chargement_fiche'] = $fichesSortie[$idTicket]['date_chargement'];
                $ticket['poids_parc'] = $fichesSortie[$idTicket]['poids_parc'];
                $ticket['prix_unitaire_transport'] = $fichesSortie[$idTicket]['prix_unitaire_transport'];
                $ticket['poids_unitaire_regime'] = $fichesSortie[$idTicket]['poids_unitaire_regime'];
                $ticket['nom_produit'] = $fichesSortie[$idTicket]['nom_produit'];
            } else {
                $ticket['fiche_id'] = null;
                $ticket['origine'] = '';
                $ticket['date_chargement_fiche'] = '';
                $ticket['poids_parc'] = '';
                $ticket['prix_unitaire_transport'] = null;
                $ticket['poids_unitaire_regime'] = null;
                $ticket['nom_produit'] = null;
            }
        }
        unset($ticket);

        // Récupérer les véhicules depuis l'API pour le modal
        $vehiculesApi = [];
        try {
            $vehiculesResponse = Http::acceptJson()
                ->withoutVerifying()
                ->timeout($timeout)
                ->get('https://api.objetombrepegasus.online/api/camions/mes_camions.php');
            if ($vehiculesResponse->successful()) {
                $vehiculesApi = $vehiculesResponse->json('vehicules') ?? [];
            }
        } catch (\Throwable $e) {}

        // Récupérer les agents du groupe PGF depuis la base locale
        $groupePgf = Groupe::where('nom_groupe', 'Groupe PGF')->first();
        $agentsPgf = [];
        $vehiculesPgf = [];
        if ($groupePgf) {
            // Agents PGF
            $groupeAgents = GroupeAgent::where('groupe_id', $groupePgf->id)->get();
            $agentsById = [];
            foreach ($agentsApi as $a) {
                $agentsById[$a['id_agent'] ?? 0] = $a;
            }
            foreach ($groupeAgents as $ga) {
                if (isset($agentsById[$ga->id_agent])) {
                    $agentsPgf[] = $agentsById[$ga->id_agent];
                } else {
                    $agentsPgf[] = [
                        'id_agent' => $ga->id_agent,
                        'nom_complet' => 'Agent #' . $ga->id_agent,
                        'numero_agent' => $ga->type_agent ?? '',
                    ];
                }
            }
        }

        // Véhicules PGF - chercher dans tous les groupes contenant "PGF"
        $groupesPgf = Groupe::where('nom_groupe', 'like', '%PGF%')->pluck('id')->toArray();
        if (!empty($groupesPgf)) {
            $vehiculesPgfIds = GroupeVehicule::whereIn('groupe_id', $groupesPgf)->pluck('vehicule_id')->toArray();
            $vehiculesPgf = array_filter($vehiculesApi, function ($v) use ($vehiculesPgfIds) {
                return in_array($v['vehicules_id'] ?? 0, $vehiculesPgfIds);
            });
            $vehiculesPgf = array_values($vehiculesPgf);
        }

        $groupesParticuliers = ParticulierGroupe::with('agents')
            ->orderBy('nom_groupe')
            ->get();

        // Ponts gérables (depuis pont_etats)
        $pontEtatsGerables = PontEtat::where('gerable', true)->get();
        $idsPontsGerables = $pontEtatsGerables->pluck('id_pont')->toArray();

        // Récupérer les ponts API et filtrer les gérables
        $mesPontsUrl = (string) config('services.external_auth.mes_ponts_url');
        $pontsApi = [];
        try {
            $pontResponse = Http::acceptJson()->withoutVerifying()->timeout($timeout)->get($mesPontsUrl);
            if ($pontResponse->successful()) {
                $pontsApi = $pontResponse->json('ponts') ?? [];
            }
        } catch (\Throwable $e) {}

        $pontsGerables = array_values(array_filter($pontsApi, function ($p) use ($idsPontsGerables) {
            return in_array((int) ($p['id_pont'] ?? 0), $idsPontsGerables, true);
        }));

        // Tous les ponts annotés avec leur statut gérable
        $tousLesPonts = array_map(function ($p) use ($idsPontsGerables) {
            $p['gerable'] = in_array((int) ($p['id_pont'] ?? 0), $idsPontsGerables, true);
            return $p;
        }, $pontsApi);

        // Produits locaux
        $produitsLocaux = Produit::orderBy('nom')->get();

        // Usines par produit pour le select dynamique
        $usinesParProduit = app(UsinesParProduitService::class)->usinesParProduitPourSelect();

        // Parcs par pont + produit (depuis stocks ouverts)
        // Structure : [ id_pont => [ produit_id => [ {id, nom, code} ] ] ]
        $parcsParPontProduit = [];
        $stocksOuverts = Stock::where('type', 'entree')
            ->where('statut', 'ouvert')
            ->whereNotNull('id_pont')
            ->whereNotNull('produit_id')
            ->with('parc')
            ->get();

        foreach ($stocksOuverts as $stock) {
            $idPont = (int) $stock->id_pont;
            $produitId = (int) $stock->produit_id;
            $parc = $stock->parc ?? Parc::find($stock->parc_id);
            if (!$parc) continue;
            $parcsParPontProduit[$idPont][$produitId][] = [
                'id' => $parc->id,
                'nom' => $parc->nom,
                'code' => $parc->code,
            ];
        }

        return view('tickets.index', [
            'tickets' => $ticketsArray,
            'pagination' => $pagination,
            'vehicules' => $vehicules,
            'vehiculesApi' => $vehiculesApi,
            'vehiculesPgf' => $vehiculesPgf,
            'usines' => $usinesApi,
            'agents' => $agentsApi,
            'agentsPgf' => $agentsPgf,
            'groupesParticuliers' => $groupesParticuliers,
            'pontsGerables' => $pontsGerables,
            'tousLesPonts' => $tousLesPonts,
            'produitsLocaux' => $produitsLocaux,
            'usinesParProduit' => $usinesParProduit,
            'parcsParPontProduit' => $parcsParPontProduit,
            'external_error' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero_ticket'        => ['required', 'string', 'max:255'],
            'date_ticket'          => ['required', 'date'],
            'matricule_vehicule'   => ['required', 'string', 'max:255'],
            'vehicule_id'          => ['nullable', 'integer', 'min:1'],
            'poids'                => ['nullable', 'numeric', 'min:0'],
            'id_usine'             => ['required', 'integer', 'min:1'],
            'particulier_groupe_id'=> ['required', 'exists:particulier_groupes,id'],
            'particulier_agent_id' => ['required', 'exists:particulier_agents,id'],
            'prix_unitaire'        => ['nullable', 'numeric', 'min:0'],
            'statut_ticket'        => ['nullable', 'in:soldé,non soldé'],
            'id_pont'              => ['nullable', 'integer', 'min:1'],
            'parc_id'              => ['nullable', 'integer', 'min:1'],
            'produit_id'           => ['nullable', 'integer', 'min:1'],
        ]);

        $agent = ParticulierAgent::where('id', $validated['particulier_agent_id'])
            ->where('particulier_groupe_id', $validated['particulier_groupe_id'])
            ->first();

        if (!$agent) {
            return back()->withInput()->withErrors([
                'particulier_agent_id' => 'Cet agent n\'appartient pas au groupe sélectionné.',
            ]);
        }

        $ticket = Ticket::create([
            'numero_ticket'       => $validated['numero_ticket'],
            'date_ticket'         => $validated['date_ticket'],
            'matricule_vehicule'  => trim($validated['matricule_vehicule']),
            'vehicule_id'         => $validated['vehicule_id'] ?? null,
            'poids'               => $validated['poids'] ?? null,
            'id_usine'            => $validated['id_usine'],
            'id_agent'            => null,
            'particulier_agent_id'=> $agent->id,
            'id_utilisateur'      => Auth::id() ?? 1,
            'prix_unitaire'       => $validated['prix_unitaire'] ?? 0,
            'statut_ticket'       => $validated['statut_ticket'] ?? 'non soldé',
        ]);

        // Si pont + parc + produit fournis → créer une FicheSortie liée au stock
        $idPont   = $validated['id_pont'] ?? null;
        $parcId   = $validated['parc_id'] ?? null;
        $produitId = $validated['produit_id'] ?? null;

        if ($idPont && $produitId) {
            // Trouver le stock ouvert actif pour ce pont + produit (+ parc si fourni)
            $stockQuery = Stock::where('id_pont', $idPont)
                ->where('produit_id', $produitId)
                ->where('type', 'entree')
                ->where('statut', 'ouvert')
                ->where(fn ($q) => $q->whereNull('etat')->orWhere('etat', 'actif'));

            if ($parcId) {
                $stockQuery->where('parc_id', $parcId);
            }

            $stock = $stockQuery->orderBy('id')->first();

            if ($stock) {
                $parc    = $parcId ? Parc::find($parcId) : null;
                $produit = Produit::find($produitId);

                // Récupérer infos pont depuis pont_etats
                $pontEtat = PontEtat::where('id_pont', $idPont)->first();
                $nomPont  = $pontEtat?->nom_pont ?? '';
                $codePont = $pontEtat?->code_pont ?? '';

                // Récupérer le nom de l'usine depuis l'API
                $nomUsine = (string) $ticket->id_usine;
                try {
                    $timeout = (int) config('services.external_auth.timeout', 10);
                    $usinesUrl = (string) config('services.external_auth.mes_usines_url');
                    $usinesResp = Http::acceptJson()->withoutVerifying()->timeout($timeout)->get($usinesUrl);
                    if ($usinesResp->successful()) {
                        foreach ($usinesResp->json('usines') ?? [] as $u) {
                            if ((int)($u['id_usine'] ?? 0) === (int)$ticket->id_usine) {
                                $nomUsine = $u['nom_usine'] ?? $nomUsine;
                                break;
                            }
                        }
                    }
                } catch (\Throwable $e) {}

                // Nom de l'agent depuis le modèle ParticulierAgent
                $nomAgent = trim($agent->nom . ' ' . $agent->prenoms);
                $numeroAgent = $agent->numero_agent ?? '';

                FicheSortie::create([
                    'stock_id'           => $stock->id,
                    'parc_id'            => $parc?->id,
                    'nom_parc'           => $parc?->nom ?? '',
                    'vehicule_id'        => $ticket->vehicule_id ?? 0,
                    'matricule_vehicule' => $ticket->matricule_vehicule,
                    'id_pont'            => $idPont,
                    'nom_pont'           => $nomPont,
                    'code_pont'          => $codePont,
                    'id_agent'           => $agent->id_agent ?? 0,
                    'nom_agent'          => $nomAgent,
                    'numero_agent'       => $numeroAgent,
                    'usine'              => $nomUsine,
                    'produit_id'         => $produit?->id,
                    'nom_produit'        => $produit?->nom ?? '',
                    'id_ticket'          => $ticket->id_ticket,
                    'numero_ticket'      => $ticket->numero_ticket,
                    'date_chargement'    => $ticket->date_ticket,
                    'date_dechargement'  => $ticket->date_ticket,
                    'poids_pont'         => $ticket->poids ?? 0,
                ]);
            }
        }

        return redirect()->route('tickets.index')
            ->with('success', 'Ticket créé avec succès.');
    }

    public function confirmUnipalm(Request $request, int $id)
    {
        $ticket = Ticket::findOrFail($id);

        // Récupérer les tickets depuis l'API Unipalm
        $timeout = 10;
        $ticketsApi = [];

        try {
            $response = Http::acceptJson()
                ->withoutVerifying()
                ->timeout($timeout)
                ->get('https://api.objetombrepegasus.online/api/camions/mes_tickets.php');
            if ($response->successful()) {
                $ticketsApi = $response->json('tickets') ?? [];
            }
        } catch (\Throwable $e) {
            return redirect()->route('tickets.index')
                ->with('error', 'Impossible de joindre l\'API Unipalm.');
        }

        // Récupérer les noms d'usine et agent depuis l'API pour la comparaison
        $usinesApi = [];
        $agentsApi = [];
        try {
            $usinesResponse = Http::acceptJson()->withoutVerifying()->timeout($timeout)
                ->get('https://api.objetombrepegasus.online/api/camions/mes_usines.php');
            if ($usinesResponse->successful()) {
                $usinesApi = $usinesResponse->json('usines') ?? [];
            }
        } catch (\Throwable $e) {}

        try {
            $agentsResponse = Http::acceptJson()->withoutVerifying()->timeout($timeout)
                ->get('https://api.objetombrepegasus.online/api/camions/mes_agents.php');
            if ($agentsResponse->successful()) {
                $agentsApi = $agentsResponse->json('agents') ?? [];
            }
        } catch (\Throwable $e) {}

        // Indexer par ID
        $usinesById = [];
        foreach ($usinesApi as $u) {
            $usinesById[$u['id_usine'] ?? 0] = $u['nom_usine'] ?? '';
        }
        $agentsById = [];
        foreach ($agentsApi as $a) {
            $agentsById[$a['id_agent'] ?? 0] = $a['nom_complet'] ?? '';
        }

        // Préparer les données du ticket local pour comparaison
        $ticketDate = $ticket->date_ticket ? $ticket->date_ticket->format('Y-m-d') : '';
        $ticketNumero = $ticket->numero_ticket;
        $ticketUsine = $usinesById[$ticket->id_usine] ?? '';
        $ticketAgent = $agentsById[$ticket->id_agent] ?? '';
        $ticketPoids = (float) $ticket->poids;

        // Chercher un ticket correspondant dans l'API
        $ticketTrouve = null;
        foreach ($ticketsApi as $apiTicket) {
            $apiDate = $apiTicket['date_ticket'] ?? '';
            $apiNumero = $apiTicket['numero_ticket'] ?? ($apiTicket['num_ticket'] ?? '');
            $apiUsine = $apiTicket['nom_usine'] ?? ($apiTicket['usine'] ?? '');
            $apiPoids = (float) ($apiTicket['poids'] ?? ($apiTicket['poids_usine'] ?? 0));

            // Comparaison - Date, N°Ticket, Usine, Poids
            $matchDate = ($ticketDate === $apiDate);
            $matchNumero = (strtolower(trim($ticketNumero)) === strtolower(trim($apiNumero)));
            $matchUsine = (strtolower(trim($ticketUsine)) === strtolower(trim($apiUsine)));
            $matchPoids = (abs($ticketPoids - $apiPoids) < 10); // Tolérance de 10 kg

            if ($matchDate && $matchNumero && $matchUsine && $matchPoids) {
                $ticketTrouve = $apiTicket;
                break;
            }
        }

        if ($ticketTrouve) {
            $ticket->update([
                'conformite' => 'conforme',
                'poids_unipalm' => $ticketTrouve['poids'] ?? null,
                'date_confirmation_unipalm' => now(),
            ]);
            return redirect()->route('tickets.index')
                ->with('success', 'Ticket confirmé avec Unipalm ! Correspondance trouvée: N°' . ($ticketTrouve['numero_ticket'] ?? '') . ', Poids: ' . number_format((float)($ticketTrouve['poids'] ?? 0), 0, ',', ' ') . ' kg');
        } else {
            $ticket->update([
                'conformite' => 'non conforme',
                'date_confirmation_unipalm' => now(),
            ]);
            return redirect()->route('tickets.index')
                ->with('error', 'Aucun ticket correspondant trouvé dans Unipalm. Vérifiez les données (Date, N°Ticket, Usine, Poids).');
        }
    }

    public function exportBordereauPdf(int $id)
    {
        $ticket = Ticket::with('particulierAgent.groupe')->findOrFail($id);

        $nomUsine = $this->nomUsinePourTicket($ticket->id_usine);
        $chargeMission = $ticket->particulierAgent
            ? $ticket->particulierAgent->nom_complet
            : '—';

        $dateTicket = $ticket->date_ticket
            ? Carbon::parse($ticket->date_ticket)
            : now();
        $dateReception = $ticket->created_at
            ? Carbon::parse($ticket->created_at)
            : $dateTicket;

        $formatCourt = fn (Carbon $d) => $d->format('d/m/y');

        $poids = (float) ($ticket->poids ?? 0);
        $poidsFormate = number_format($poids, 0, ',', ' ');

        $prixUnitaire = null;
        $montantCalcule = null;
        if ($ticket->particulier_agent_id && $ticket->id_usine) {
            $prixRecords = ParticulierAgentPrix::where('particulier_agent_id', $ticket->particulier_agent_id)->get();
            $prixUnitaire = $this->prixUnitaireParticulierAgent(
                $prixRecords,
                (int) $ticket->particulier_agent_id,
                (int) $ticket->id_usine,
                $ticket->date_ticket?->format('Y-m-d')
            );
            if ($prixUnitaire !== null && $poids > 0) {
                $montantCalcule = $prixUnitaire * $poids;
            }
        }
        if ($montantCalcule === null && (float) ($ticket->prix_unitaire ?? 0) > 0 && $poids > 0) {
            $prixUnitaire = (float) $ticket->prix_unitaire;
            $montantCalcule = $prixUnitaire * $poids;
        }

        $montantFormate = $montantCalcule !== null
            ? number_format($montantCalcule, 0, ',', ' ')
            : '—';

        $logoPath = public_path('img/logo/logo.png');
        if (!is_file($logoPath)) {
            $logoPath = null;
        }

        $pdf = Pdf::loadView('tickets.bordereau_pdf', [
            'logoPath' => $logoPath,
            'chargeMission' => strtoupper($chargeMission),
            'periodeDebut' => $formatCourt($dateTicket),
            'periodeFin' => $formatCourt($dateTicket),
            'nomUsine' => strtoupper($nomUsine),
            'ligne' => [
                'date_reception' => $formatCourt($dateReception),
                'date_ticket' => $formatCourt($dateTicket),
                'vehicule' => $ticket->matricule_vehicule ?? '—',
                'numero_ticket' => $ticket->numero_ticket ?? '—',
                'poids' => $poidsFormate,
                'montant' => $montantFormate,
            ],
            'lieu' => 'Divo',
            'dateDocument' => now()->format('d/m/y'),
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = 'bordereau_' . preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string) $ticket->numero_ticket) . '.pdf';

        return $pdf->stream($filename);
    }

    private function nomUsinePourTicket(?int $idUsine): string
    {
        if (!$idUsine) {
            return '—';
        }

        try {
            $response = Http::acceptJson()
                ->withoutVerifying()
                ->timeout(10)
                ->get('https://api.objetombrepegasus.online/api/camions/mes_usines.php');

            if ($response->successful()) {
                foreach ($response->json('usines') ?? [] as $usine) {
                    if ((int) ($usine['id_usine'] ?? 0) === $idUsine) {
                        return (string) ($usine['nom_usine'] ?? '—');
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        return 'Usine #' . $idUsine;
    }

    /**
     * Mettre à jour un ticket
     */
    public function update(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $ticket->update([
            'numero_ticket' => $request->input('numero_ticket'),
            'date_ticket' => $request->input('date_ticket'),
            'matricule_vehicule' => $request->input('matricule_vehicule'),
            'poids' => $request->input('poids'),
            'poids_parc' => $request->input('poids_parc'),
            'prix_unitaire_transport' => $request->input('prix_unitaire_transport'),
        ]);

        return redirect()->route('tickets.index')
            ->with('success', 'Ticket modifié avec succès.');
    }

    /**
     * Supprimer un ticket
     */
    public function destroy($id)
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->delete();

        return redirect()->route('tickets.index')
            ->with('success', 'Ticket supprimé avec succès.');
    }

    /**
     * Afficher les tickets Unipalm (API) filtrés par les camions du groupe PGF
     */
    public function unipalm(Request $request)
    {
        $timeout = 10;
        $page = max(1, (int) $request->query('page', 1));

        // Récupérer le groupe PGF
        $groupePgf = Groupe::where('nom_groupe', 'PGF')->first();
        $vehiculesPgfIds = [];
        $vehiculesPgfMatricules = [];

        if ($groupePgf) {
            $groupeVehicules = GroupeVehicule::where('groupe_id', $groupePgf->id)->get();
            $vehiculesPgfIds = $groupeVehicules->pluck('vehicule_id')->toArray();
            $vehiculesPgfMatricules = $groupeVehicules->pluck('matricule_vehicule')->toArray();
        }

        // Récupérer les tickets depuis l'API
        $tickets = [];
        $pagination = [
            'current_page' => $page,
            'per_page' => 20,
            'total' => 0,
            'last_page' => 1,
        ];

        try {
            $response = Http::acceptJson()
                ->withoutVerifying()
                ->timeout($timeout)
                ->get('https://api.objetombrepegasus.online/api/camions/mes_tickets.php', [
                    'page' => $page,
                ]);

            if ($response->successful()) {
                $allTickets = $response->json('tickets') ?? [];
                $apiPagination = $response->json('pagination') ?? [];

                // Filtrer les tickets par les véhicules du groupe PGF
                $tickets = array_filter($allTickets, function ($t) use ($vehiculesPgfIds, $vehiculesPgfMatricules) {
                    $vehiculeId = $t['vehicule_id'] ?? 0;
                    $matricule = $t['matricule_vehicule'] ?? '';
                    return in_array($vehiculeId, $vehiculesPgfIds) || in_array($matricule, $vehiculesPgfMatricules);
                });
                $tickets = array_values($tickets);

                // Récupérer les fiches de sortie associées aux tickets
                $ticketIds = array_column($tickets, 'id_ticket');
                $fichesAssociees = [];
                if (!empty($ticketIds)) {
                    $fiches = FicheSortie::whereIn('id_ticket', $ticketIds)->get()->keyBy('id_ticket');
                    foreach ($fiches as $idTicket => $fiche) {
                        $fichesAssociees[$idTicket] = [
                            'origine' => $fiche->nom_pont,
                            'date_chargement' => $fiche->date_chargement ? $fiche->date_chargement->format('d-m-Y') : '-',
                            'poids_parc' => $fiche->poids_pont,
                        ];
                    }
                }

                // Enrichir les tickets avec les données de fiche de sortie
                foreach ($tickets as &$ticket) {
                    $idTicket = $ticket['id_ticket'] ?? 0;
                    if (isset($fichesAssociees[$idTicket])) {
                        $ticket['origine'] = $fichesAssociees[$idTicket]['origine'];
                        $ticket['date_chargement'] = $fichesAssociees[$idTicket]['date_chargement'];
                        $ticket['poids_parc'] = $fichesAssociees[$idTicket]['poids_parc'];
                        $ticket['has_fiche'] = true;
                    } else {
                        $ticket['has_fiche'] = false;
                    }
                }
                unset($ticket);

                $pagination = [
                    'current_page' => $apiPagination['current_page'] ?? $page,
                    'per_page' => $apiPagination['per_page'] ?? 20,
                    'total' => count($tickets),
                    'last_page' => $apiPagination['last_page'] ?? 1,
                ];
            }
        } catch (\Throwable $e) {}

        // Récupérer les fiches de sortie disponibles (non associées à un ticket)
        $fichesDisponibles = FicheSortie::whereNull('id_ticket')
            ->orderBy('date_chargement', 'desc')
            ->get();

        return view('tickets.unipalm', [
            'tickets' => $tickets,
            'pagination' => $pagination,
            'groupe_pgf' => $groupePgf,
            'fiches_disponibles' => $fichesDisponibles,
        ]);
    }

    public function associerFiche(Request $request)
    {
        $validated = $request->validate([
            'id_ticket' => ['required', 'integer'],
            'numero_ticket' => ['required', 'string'],
            'fiche_id' => ['required', 'integer', 'exists:fiches_sortie,id'],
        ]);

        $fiche = FicheSortie::findOrFail($validated['fiche_id']);
        $fiche->update([
            'id_ticket' => $validated['id_ticket'],
            'numero_ticket' => $validated['numero_ticket'],
        ]);

        return redirect()->route('tickets.unipalm')
            ->with('success', 'Fiche de sortie associée au ticket avec succès.');
    }
}
