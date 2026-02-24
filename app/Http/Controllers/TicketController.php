<?php

namespace App\Http\Controllers;

use App\Models\FicheSortie;
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
            $agentsResponse = Http::acceptJson()
                ->timeout($timeout)
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

        return view('tickets.index', [
            'tickets' => $ticketsArray,
            'pagination' => $pagination,
            'vehicules' => $vehicules,
            'vehiculesApi' => $vehiculesApi,
            'usines' => $usinesApi,
            'agents' => $agentsApi,
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
}
