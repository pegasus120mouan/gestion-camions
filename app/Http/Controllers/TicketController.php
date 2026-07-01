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
use App\Models\Produit;
use App\Models\Stock;
use App\Models\Ticket;
use App\Services\ChefEquipeContext;
use App\Services\FicheSortieDechargementService;
use App\Services\FicheSortieNumeroService;
use App\Services\MesAgentsService;
use App\Services\MesTicketsService;
use App\Services\ParticulierAgentsApiService;
use App\Services\TicketPrixService;
use App\Services\UsinesParProduitService;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    public function __construct(
        private TicketPrixService $ticketPrixService,
        private ParticulierAgentsApiService $particulierAgentsApiService,
        private MesAgentsService $mesAgentsService,
        private MesTicketsService $mesTicketsService,
    ) {}

    public function index(Request $request)
    {
        $vehicule = trim((string) $request->query('vehicule', ''));
        $usine = trim((string) $request->query('usine', ''));
        $agent = trim((string) $request->query('agent', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = 20;
        $hasFilters = $vehicule !== '' || $usine !== '' || $agent !== '';

        $agentsApi = $this->mesAgentsService->fetchAllAgents([], $request);
        $externalError = null;
        $filteredTickets = null;

        if ($hasFilters) {
            $allTickets = $this->mesTicketsService->fetchAllTickets([], $request);
            $filteredTickets = $this->mesTicketsService->filterTickets($allTickets, $vehicule, $usine, $agent);
            $total = count($filteredTickets);
            $lastPage = max(1, (int) ceil($total / $perPage));
            $page = min($page, $lastPage);
            $ticketsApi = array_slice($filteredTickets, ($page - 1) * $perPage, $perPage);
            $pagination = [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
            ];
        } else {
            $result = $this->mesTicketsService->listTickets(['page' => $page, 'per_page' => $perPage], $request);
            $ticketsApi = $result['tickets'];
            $pagination = $result['pagination'] ?? [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => count($ticketsApi),
                'last_page' => 1,
            ];
            $externalError = $result['error'];
        }

        // Récupérer les usines depuis l'API pour les noms
        $timeout = 10;
        $usinesApi = [];

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

        $ticketIds = array_column($ticketsApi, 'id_ticket');
        $localTickets = $ticketIds !== []
            ? Ticket::with('particulierAgent.groupe')->whereIn('id_ticket', $ticketIds)->get()->keyBy('id_ticket')
            : collect();

        $particulierAgentIds = $localTickets->pluck('particulier_agent_id')->filter()->unique()->values();
        $prixParticuliers = $particulierAgentIds->isNotEmpty()
            ? ParticulierAgentPrix::whereIn('particulier_agent_id', $particulierAgentIds)->get()
            : collect();

        $ticketsArray = [];
        foreach ($ticketsApi as $ticket) {
            $idTicket = (int) ($ticket['id_ticket'] ?? 0);
            $local = $localTickets->get($idTicket);

            $ticketsArray[] = [
                'id_ticket' => $idTicket,
                'numero_ticket' => $ticket['numero_ticket'] ?? '',
                'date_ticket' => $ticket['date_ticket'] ?? null,
                'matricule_vehicule' => $ticket['matricule_vehicule'] ?? '',
                'vehicule_id' => (int) ($ticket['vehicule_id'] ?? 0),
                'poids' => $ticket['poids'] ?? 0,
                'id_usine' => (int) ($ticket['id_usine'] ?? 0),
                'nom_usine' => $ticket['nom_usine'] ?: ($usinesById[$ticket['id_usine'] ?? 0] ?? '-'),
                'id_agent' => (int) ($ticket['id_agent'] ?? 0),
                'nom_agent' => $local?->particulierAgent
                    ? $local->particulierAgent->nom_complet
                    : (($ticket['nom_agent'] ?? '-') !== '-' ? $ticket['nom_agent'] : ($agentsById[$ticket['id_agent'] ?? 0] ?? '-')),
                'nom_groupe' => $local?->particulierAgent?->groupe?->nom_groupe ?? '-',
                'particulier_agent_id' => $local?->particulier_agent_id,
                'prix_unitaire' => $ticket['prix_unitaire'] ?? $local?->prix_unitaire,
                'prix_unitaire_agent' => null,
                'montant_calcule' => null,
                'montant_paie' => $ticket['montant_paie'] ?? $local?->montant_paie,
                'statut_ticket' => $ticket['statut_ticket'] ?? $local?->statut_ticket,
                'created_at' => $ticket['created_at'] ?? ($local?->created_at?->format('Y-m-d H:i:s')),
                'conformite' => $local?->conformite,
            ];
        }

        $vehiculesSource = $hasFilters
            ? $filteredTickets
            : $ticketsApi;
        $vehicules = collect($vehiculesSource)
            ->pluck('matricule_vehicule')
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Récupérer les fiches de sortie associées aux tickets
        $fichesNonDechargees = $this->fichesNonDechargeesPourChef($request);
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
                    'produit_id' => $fiche->produit_id,
                    'id_agent' => $fiche->id_agent,
                    'nom_agent' => $fiche->nom_agent,
                ];
            }
        }

        $ticketsById = $localTickets;

        // Ajouter les infos de fiche de sortie et calculer le prix unitaire
        foreach ($ticketsArray as &$ticket) {
            $idTicket = $ticket['id_ticket'] ?? null;
            $produitId = null;
            $idAgentFiche = null;

            if ($idTicket && isset($fichesSortie[$idTicket])) {
                $ticket['fiche_id'] = $fichesSortie[$idTicket]['fiche_id'];
                $ticket['origine'] = $fichesSortie[$idTicket]['origine'];
                $ticket['date_chargement_fiche'] = $fichesSortie[$idTicket]['date_chargement'];
                $ticket['poids_parc'] = $fichesSortie[$idTicket]['poids_parc'];
                $ticket['prix_unitaire_transport'] = $fichesSortie[$idTicket]['prix_unitaire_transport'];
                $ticket['poids_unitaire_regime'] = $fichesSortie[$idTicket]['poids_unitaire_regime'];
                $ticket['nom_produit'] = $fichesSortie[$idTicket]['nom_produit'];
                $produitId = $fichesSortie[$idTicket]['produit_id'] ?? null;
                $idAgentFiche = !empty($fichesSortie[$idTicket]['id_agent'])
                    ? (int) $fichesSortie[$idTicket]['id_agent']
                    : null;
                if (($ticket['nom_agent'] ?? '-') === '-' && !empty($fichesSortie[$idTicket]['nom_agent'])) {
                    $ticket['nom_agent'] = $fichesSortie[$idTicket]['nom_agent'];
                }
            } else {
                $ticket['fiche_id'] = null;
                $ticket['origine'] = '';
                $ticket['date_chargement_fiche'] = '';
                $ticket['poids_parc'] = '';
                $ticket['prix_unitaire_transport'] = null;
                $ticket['poids_unitaire_regime'] = null;
                $ticket['nom_produit'] = null;
            }

            $modeleTicket = $ticketsById->get($idTicket) ?? $this->ticketModeleDepuisApi($ticket);
            $local = $ticketsById->get($idTicket);
            $produitId = $this->resoudreProduitIdPourTicket($ticket, $produitId, $fichesNonDechargees);

            $nomUsine = trim((string) ($ticket['nom_usine'] ?? ''));
            if ($nomUsine === '' || $nomUsine === '-') {
                $nomUsine = $usinesById[$ticket['id_usine'] ?? 0] ?? '';
            }

            $prixStocke = (float) ($local?->prix_unitaire ?? $ticket['prix_unitaire'] ?? 0);
            $montantStocke = (float) ($local?->montant_paie ?? $ticket['montant_paie'] ?? 0);

            if ($prixStocke > 0) {
                $prixUnitaireAgent = $prixStocke;
            } else {
                $prixUnitaireAgent = $this->ticketPrixService->prixUnitairePourTicket(
                    $modeleTicket,
                    $produitId,
                    $ticket['date_ticket'] ?? null,
                    $prixParticuliers,
                    $idAgentFiche,
                    $nomUsine !== '' ? $nomUsine : null,
                );
            }
            $ticket['prix_unitaire_agent'] = $prixUnitaireAgent;

            if ($montantStocke > 0) {
                $ticket['montant_calcule'] = $montantStocke;
            } elseif ($prixUnitaireAgent !== null && (float) ($ticket['poids'] ?? 0) > 0) {
                $ticket['montant_calcule'] = $prixUnitaireAgent * (float) $ticket['poids'];
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

        $agentsParGroupe = $this->particulierAgentsApiService->agentsParGroupePourSelect(
            $groupesParticuliers,
            $agentsApi
        );

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
            if (!$parc) {
                continue;
            }
            $dejaPresent = collect($parcsParPontProduit[$idPont][$produitId] ?? [])
                ->contains(fn ($p) => (int) ($p['id'] ?? 0) === (int) $parc->id);
            if ($dejaPresent) {
                continue;
            }
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
            'agentsParGroupe' => $agentsParGroupe,
            'pontsGerables' => $pontsGerables,
            'tousLesPonts' => $tousLesPonts,
            'produitsLocaux' => $produitsLocaux,
            'usinesParProduit' => $usinesParProduit,
            'parcsParPontProduit' => $parcsParPontProduit,
            'external_error' => $externalError,
            'fichesNonDechargees' => $fichesNonDechargees,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, FicheSortie>
     */
    private function fichesNonDechargeesPourChef(Request $request)
    {
        $chefAgentIds = $this->mesAgentsService->chefAgentIds($request);
        if ($chefAgentIds === []) {
            return collect();
        }

        return FicheSortie::query()
            ->whereNull('date_dechargement')
            ->whereIn('id_agent', $chefAgentIds)
            ->orderByDesc('date_chargement')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $ticket
     */
    private function resoudreProduitIdPourTicket(
        array $ticket,
        ?int $produitFromFicheLiee,
        \Illuminate\Support\Collection $fichesNonDechargees
    ): ?int {
        if ($produitFromFicheLiee) {
            return (int) $produitFromFicheLiee;
        }

        $idAgent = (int) ($ticket['id_agent'] ?? 0);
        $vehicule = strtoupper(trim((string) ($ticket['matricule_vehicule'] ?? '')));
        $usine = trim((string) ($ticket['nom_usine'] ?? ''));
        if ($usine === '' || $usine === '-') {
            $usine = '';
        }

        if ($idAgent <= 0) {
            return null;
        }

        $candidat = $fichesNonDechargees->first(function (FicheSortie $f) use ($idAgent, $vehicule, $usine) {
            if ((int) $f->id_agent !== $idAgent || !$f->produit_id) {
                return false;
            }
            if ($vehicule !== '' && strcasecmp(trim((string) $f->matricule_vehicule), $vehicule) !== 0) {
                return false;
            }
            if ($usine !== '' && $f->usine && strcasecmp(trim((string) $f->usine), $usine) !== 0) {
                return false;
            }

            return true;
        });

        return $candidat?->produit_id ? (int) $candidat->produit_id : null;
    }

    /**
     * @param  array<string, mixed>  $ticket
     */
    private function ticketModeleDepuisApi(array $ticket): Ticket
    {
        $modele = new Ticket([
            'id_ticket' => (int) ($ticket['id_ticket'] ?? 0),
            'id_usine' => (int) ($ticket['id_usine'] ?? 0),
            'id_agent' => (int) ($ticket['id_agent'] ?? 0),
            'matricule_vehicule' => (string) ($ticket['matricule_vehicule'] ?? ''),
            'particulier_agent_id' => $ticket['particulier_agent_id'] ?? null,
            'poids' => $ticket['poids'] ?? null,
        ]);

        if (!empty($ticket['date_ticket'])) {
            try {
                $modele->date_ticket = Carbon::parse((string) $ticket['date_ticket']);
            } catch (\Throwable $e) {
            }
        }

        return $modele;
    }

    public function store(Request $request)
    {
        $request->merge([
            'numero_ticket' => trim((string) $request->input('numero_ticket', '')),
        ]);

        $idPontSaisi = $request->filled('id_pont') ? (int) $request->input('id_pont') : null;
        $pontGerable = $idPontSaisi
            && PontEtat::where('id_pont', $idPontSaisi)->where('gerable', true)->exists();

        $validated = $request->validate([
            'numero_ticket'        => ['required', 'string', 'max:255', Rule::unique('tickets', 'numero_ticket')],
            'date_ticket'          => ['required', 'date'],
            'matricule_vehicule'   => ['required', 'string', 'max:255'],
            'vehicule_id'          => ['nullable', 'integer', 'min:1'],
            'poids'                => ['nullable', 'numeric', 'min:0'],
            'id_usine'             => ['required', 'integer', 'min:1'],
            'particulier_groupe_id'=> ['required', 'exists:particulier_groupes,id'],
            'agent_ref'            => ['required', 'string', 'regex:/^(api|local):\d+$/'],
            'prix_unitaire'        => ['nullable', 'numeric', 'min:0'],
            'statut_ticket'        => ['nullable', 'in:soldé,non soldé'],
            'id_pont'              => ['nullable', 'integer', 'min:1'],
            'parc_id'              => [$pontGerable ? 'required' : 'nullable', 'integer', 'min:1'],
            'produit_id'           => [$pontGerable ? 'required' : 'nullable', 'integer', 'min:1'],
        ], [
            'numero_ticket.unique' => 'Ce N° ticket existe déjà.',
            'parc_id.required' => 'Le parc est obligatoire pour un pont gérable.',
            'produit_id.required' => 'Le produit est obligatoire pour un pont gérable.',
        ]);

        if ($pontGerable) {
            $parcId = (int) $validated['parc_id'];
            $produitId = (int) $validated['produit_id'];
            $parcValide = Stock::where('id_pont', $idPontSaisi)
                ->where('produit_id', $produitId)
                ->where('parc_id', $parcId)
                ->where('type', 'entree')
                ->where('statut', 'ouvert')
                ->exists();

            if (!$parcValide) {
                return back()->withInput()->withErrors([
                    'parc_id' => 'Parc invalide ou indisponible pour ce pont et ce produit.',
                ]);
            }
        }

        $agentsApi = $this->particulierAgentsApiService->fetchAll($request);
        $agent = $this->particulierAgentsApiService->resolveAgentForTicket(
            (int) $validated['particulier_groupe_id'],
            (string) $validated['agent_ref'],
            $agentsApi
        );

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

                $numeroFiche = app(FicheSortieNumeroService::class)->generer($nomPont, $idPont);

                FicheSortie::create([
                    'numero_fiche'       => $numeroFiche,
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

    public function valider(Request $request, int $id, FicheSortieDechargementService $dechargementService)
    {
        $validated = $request->validate([
            'fiche_id' => ['required', 'integer', 'exists:fiches_sortie,id'],
            'parc_id' => ['nullable', 'integer', 'exists:parcs,id'],
        ]);

        $chefAgentIds = $this->mesAgentsService->chefAgentIds($request);
        $apiTicket = $this->mesTicketsService->findTicketById($id, $request);

        if (!$apiTicket) {
            return redirect()->route('tickets.index')
                ->with('error', 'Ticket introuvable pour votre équipe.');
        }

        $fiche = FicheSortie::query()
            ->whereNull('date_dechargement')
            ->whereIn('id_agent', $chefAgentIds ?: [-1])
            ->find($validated['fiche_id']);

        if (!$fiche) {
            return redirect()->route('tickets.index')
                ->with('error', 'Fiche de sortie introuvable ou déjà déchargée.');
        }

        $ticket = Ticket::find($id);
        if (!$ticket) {
            $ticket = new Ticket(['id_ticket' => $id]);
        }

        $dateTicket = $apiTicket['date_ticket'] ?? now()->format('Y-m-d');
        $poidsTicket = (float) ($apiTicket['poids'] ?? 0);
        $nomUsine = trim((string) ($apiTicket['nom_usine'] ?? $fiche->usine ?? ''));

        $ticket->fill([
            'numero_ticket' => (string) ($apiTicket['numero_ticket'] ?? ''),
            'date_ticket' => $dateTicket,
            'matricule_vehicule' => (string) ($apiTicket['matricule_vehicule'] ?? ''),
            'vehicule_id' => (int) ($apiTicket['vehicule_id'] ?? 0) ?: null,
            'poids' => $apiTicket['poids'] ?? null,
            'id_usine' => (int) ($apiTicket['id_usine'] ?? 0) ?: null,
            'id_agent' => (int) ($apiTicket['id_agent'] ?? 0) ?: null,
            'statut_ticket' => $apiTicket['statut_ticket'] ?? 'non soldé',
            'id_utilisateur' => $ticket->id_utilisateur ?? 1,
            'conformite' => 'valide',
            'poids_unipalm' => $apiTicket['poids'] ?? null,
            'date_confirmation_unipalm' => now(),
        ]);

        $prixUnitaire = $this->ticketPrixService->prixUnitairePourTicket(
            $ticket,
            $fiche->produit_id ? (int) $fiche->produit_id : null,
            $dateTicket,
            null,
            (int) ($apiTicket['id_agent'] ?? 0) ?: (int) $fiche->id_agent,
            $nomUsine !== '' ? $nomUsine : null,
        );
        $montantPaie = ($prixUnitaire !== null && $poidsTicket > 0)
            ? round($prixUnitaire * $poidsTicket, 2)
            : null;

        $ticket->prix_unitaire = $prixUnitaire ?? (float) ($apiTicket['prix_unitaire'] ?? 0);
        $ticket->montant_paie = $montantPaie ?? $apiTicket['montant_paie'] ?? null;
        try {
            DB::transaction(function () use ($ticket, $dechargementService, $fiche, $apiTicket, $dateTicket, $poidsTicket, $id, $validated) {
                $ticket->save();

                $dechargementService->decharger(
                    $fiche,
                    (string) ($apiTicket['numero_ticket'] ?? ''),
                    $dateTicket,
                    $poidsTicket,
                    $id,
                    isset($apiTicket['nom_usine']) ? (string) $apiTicket['nom_usine'] : null,
                    isset($validated['parc_id']) ? (int) $validated['parc_id'] : null,
                );
            });
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('tickets.index')
                ->with('error', $e->getMessage());
        }

        $fiche->refresh();
        $stockMsg = $dechargementService->pontEstGerable((int) $fiche->id_pont)
            ? ' Le stock du parc a été déduit.'
            : '';

        return redirect()->route('tickets.index')
            ->with('success', 'Ticket « ' . ($apiTicket['numero_ticket'] ?? $id) . ' » validé et associé à la fiche '
                . ($fiche->numero_fiche ?? $fiche->id) . '.' . $stockMsg);
    }

    public function confirmUnipalm(Request $request, int $id, ChefEquipeContext $chefContext)
    {
        $ticket = Ticket::findOrFail($id);

        // Récupérer les tickets depuis l'API Unipalm
        $timeout = 10;
        $ticketsApi = [];
        $mesTicketsUrl = (string) config('services.external_auth.mes_tickets_url');

        try {
            $response = Http::acceptJson()
                ->withoutVerifying()
                ->timeout($timeout)
                ->get($mesTicketsUrl, $chefContext->apiQueryParams($request));
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
            $agentsApi = $this->mesAgentsService->fetchAllAgents([], $request);
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

        $ficheSortie = \App\Models\FicheSortie::where('id_ticket', $ticket->id_ticket)->first();
        $chargeMission = $ticket->particulierAgent
            ? $ticket->particulierAgent->nom_complet
            : ($ficheSortie?->nom_agent ?? ($ticket->id_agent ? '#' . $ticket->id_agent : '—'));
        $nomGroupe = $ticket->particulierAgent?->groupe?->nom_groupe ?? null;

        $dateTicket = $ticket->date_ticket
            ? Carbon::parse($ticket->date_ticket)
            : now();
        $dateReception = $ticket->created_at
            ? Carbon::parse($ticket->created_at)
            : $dateTicket;

        $formatCourt = fn (Carbon $d) => $d->format('d/m/y');

        $poids = (float) ($ticket->poids ?? 0);
        $poidsFormate = number_format($poids, 0, ',', ' ');

        $produitId = $ficheSortie?->produit_id ? (int) $ficheSortie->produit_id : null;
        $idAgentFiche = $ficheSortie?->id_agent ? (int) $ficheSortie->id_agent : null;
        $nomUsinePdf = $ficheSortie?->usine ?: null;
        $prixUnitaire = $this->ticketPrixService->prixUnitairePourTicket(
            $ticket,
            $produitId,
            $ticket->date_ticket?->format('Y-m-d'),
            null,
            $idAgentFiche,
            $nomUsinePdf
        );
        $montantCalcule = $this->ticketPrixService->montantPourTicket(
            $ticket,
            $produitId,
            $ticket->date_ticket?->format('Y-m-d'),
            null,
            $idAgentFiche,
            $nomUsinePdf
        );
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
            'nomGroupe' => $nomGroupe,
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

        $request->merge([
            'numero_ticket' => trim((string) $request->input('numero_ticket', '')),
        ]);

        $validated = $request->validate([
            'numero_ticket' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tickets', 'numero_ticket')->ignore($ticket->id_ticket, 'id_ticket'),
            ],
            'date_ticket' => ['required', 'date'],
            'matricule_vehicule' => ['nullable', 'string', 'max:255'],
            'poids' => ['nullable', 'numeric', 'min:0'],
            'poids_parc' => ['nullable', 'numeric', 'min:0'],
            'prix_unitaire_transport' => ['nullable', 'numeric', 'min:0'],
        ], [
            'numero_ticket.unique' => 'Ce N° ticket existe déjà.',
        ]);

        $ticket->update([
            'numero_ticket' => $validated['numero_ticket'],
            'date_ticket' => $validated['date_ticket'],
            'matricule_vehicule' => $validated['matricule_vehicule'] ?? $ticket->matricule_vehicule,
            'poids' => $validated['poids'] ?? $ticket->poids,
        ]);

        FicheSortie::where('id_ticket', $ticket->id_ticket)->update([
            'numero_ticket' => $validated['numero_ticket'],
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

        // Supprimer la fiche de sortie liée → restitue automatiquement le poids au stock du parc
        FicheSortie::where('id_ticket', $ticket->id_ticket)->delete();

        $ticket->delete();

        return redirect()->route('tickets.index')
            ->with('success', 'Ticket supprimé avec succès.');
    }

    /**
     * Afficher les tickets Unipalm (API) filtrés par les camions du groupe PGF
     */
    public function unipalm(Request $request, ChefEquipeContext $chefContext)
    {
        $timeout = 10;
        $page = max(1, (int) $request->query('page', 1));
        $mesTicketsUrl = (string) config('services.external_auth.mes_tickets_url');

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
                ->get($mesTicketsUrl, array_merge(
                    $chefContext->apiQueryParams($request),
                    ['page' => $page],
                ));

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

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<Ticket>  $query
     * @param  list<int>  $chefAgentIds
     */
    private function appliquerFiltreAgentsChef($query, array $chefAgentIds): void
    {
        if ($chefAgentIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $particulierAgentIds = ParticulierAgent::query()
            ->whereIn('id_agent', $chefAgentIds)
            ->pluck('id');

        $query->where(function ($q) use ($chefAgentIds, $particulierAgentIds) {
            $q->whereIn('id_agent', $chefAgentIds);
            if ($particulierAgentIds->isNotEmpty()) {
                $q->orWhereIn('particulier_agent_id', $particulierAgentIds);
            }
        });
    }
}
