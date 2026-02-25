<?php

namespace App\Http\Controllers;

use App\Models\FicheSortie;
use App\Models\Groupe;
use App\Models\GroupeAgent;
use App\Models\GroupeVehicule;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class TicketController extends Controller
{
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

        $ticketsPaginated = $query->paginate(20)->withQueryString();

        // Récupérer les usines et agents depuis l'API pour les noms
        $timeout = 10;
        $usinesApi = [];
        $agentsApi = [];

        try {
            $usinesResponse = Http::acceptJson()
                ->timeout($timeout)
                ->get('https://api.objetombrepegasus.online/api/camions/mes_usines.php');
            if ($usinesResponse->successful()) {
                $usinesApi = $usinesResponse->json('usines') ?? [];
            }
        } catch (\Throwable $e) {}

        try {
            $page = 1;
            $hasMore = true;
            while ($hasMore) {
                $agentsResponse = Http::acceptJson()
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

        // Indexer par ID
        $usinesById = [];
        foreach ($usinesApi as $u) {
            $usinesById[$u['id_usine'] ?? 0] = $u['nom_usine'] ?? '';
        }
        $agentsById = [];
        foreach ($agentsApi as $a) {
            $agentsById[$a['id_agent'] ?? 0] = $a['nom_complet'] ?? '';
        }

        // Convertir en tableau pour compatibilité avec la vue existante
        $tickets = $ticketsPaginated->items();
        $ticketsArray = [];
        foreach ($tickets as $ticket) {
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
                'nom_agent' => $agentsById[$ticket->id_agent] ?? '-',
                'prix_unitaire' => $ticket->prix_unitaire,
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
            } else {
                $ticket['fiche_id'] = null;
                $ticket['origine'] = '';
                $ticket['date_chargement_fiche'] = '';
                $ticket['poids_parc'] = '';
                $ticket['prix_unitaire_transport'] = null;
                $ticket['poids_unitaire_regime'] = null;
            }
        }
        unset($ticket);

        // Récupérer les véhicules depuis l'API pour le modal
        $vehiculesApi = [];
        try {
            $vehiculesResponse = Http::acceptJson()
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

        return view('tickets.index', [
            'tickets' => $ticketsArray,
            'pagination' => $pagination,
            'vehicules' => $vehicules,
            'vehiculesApi' => $vehiculesApi,
            'vehiculesPgf' => $vehiculesPgf,
            'usines' => $usinesApi,
            'agents' => $agentsApi,
            'agentsPgf' => $agentsPgf,
            'external_error' => null,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'numero_ticket' => ['required', 'string', 'max:255'],
            'date_ticket' => ['required', 'date'],
            'vehicule_id' => ['required', 'integer', 'min:1'],
            'matricule_vehicule' => ['nullable', 'string', 'max:255'],
            'poids' => ['nullable', 'numeric', 'min:0'],
            'id_usine' => ['required', 'integer', 'min:1'],
            'id_agent' => ['required', 'integer', 'min:1'],
            'prix_unitaire' => ['nullable', 'numeric', 'min:0'],
            'statut_ticket' => ['nullable', 'in:soldé,non soldé'],
        ]);

        $ticket = Ticket::create([
            'numero_ticket' => $validated['numero_ticket'],
            'date_ticket' => $validated['date_ticket'],
            'matricule_vehicule' => $validated['matricule_vehicule'] ?? '',
            'vehicule_id' => $validated['vehicule_id'],
            'poids' => $validated['poids'] ?? null,
            'id_usine' => $validated['id_usine'],
            'id_agent' => $validated['id_agent'],
            'id_utilisateur' => Auth::id() ?? 1,
            'prix_unitaire' => $validated['prix_unitaire'] ?? 0,
            'statut_ticket' => $validated['statut_ticket'] ?? 'non soldé',
        ]);

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
            $usinesResponse = Http::acceptJson()->timeout($timeout)
                ->get('https://api.objetombrepegasus.online/api/camions/mes_usines.php');
            if ($usinesResponse->successful()) {
                $usinesApi = $usinesResponse->json('usines') ?? [];
            }
        } catch (\Throwable $e) {}

        try {
            $agentsResponse = Http::acceptJson()->timeout($timeout)
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
