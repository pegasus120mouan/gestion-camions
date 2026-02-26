<?php

namespace App\Http\Controllers;

use App\Models\Depense;
use App\Models\FicheSortie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class DepenseController extends Controller
{
    public function listeDepenses(Request $request)
    {
        $depenses = Depense::orderBy('date_depense', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        // Récupérer les véhicules depuis l'API pour le formulaire d'ajout
        $vehicules = [];
        try {
            $response = Http::acceptJson()
                ->timeout(10)
                ->get('https://api.objetombrepegasus.online/api/camions/mes_camions.php');
            if ($response->successful()) {
                $vehicules = $response->json('vehicules') ?? [];
            }
        } catch (\Throwable $e) {}

        return view('depenses.liste', [
            'depenses' => $depenses,
            'vehicules' => $vehicules,
            'external_error' => null,
        ]);
    }

    public function listeFichesSortie(Request $request)
    {
        $fiches = FicheSortie::orderBy('date_chargement', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        // Récupérer les véhicules, ponts et agents depuis l'API
        $mesCamionsUrl = (string) config('services.external_auth.mes_camions_url');
        $mesPontsUrl = (string) config('services.external_auth.mes_ponts_url');
        $mesAgentsUrl = (string) config('services.external_auth.mes_agents_url');
        $timeout = (int) config('services.external_auth.timeout', 10);
        $phpsessid = session('external_auth.phpsessid', '');

        $vehicules = [];
        $ponts = [];
        $agents = [];
        $usines = [];

        try {
            $camionsResponse = Http::acceptJson()
                ->timeout($timeout)
                ->withHeaders(['Cookie' => 'PHPSESSID=' . $phpsessid])
                ->get($mesCamionsUrl);
            if ($camionsResponse->successful()) {
                $vehicules = $camionsResponse->json('vehicules') ?? [];
            }
        } catch (\Throwable $e) {}

        try {
            $pontsResponse = Http::acceptJson()
                ->timeout($timeout)
                ->withHeaders(['Cookie' => 'PHPSESSID=' . $phpsessid])
                ->get($mesPontsUrl);
            if ($pontsResponse->successful()) {
                $ponts = $pontsResponse->json('ponts') ?? [];
            }
        } catch (\Throwable $e) {}

        try {
            $agentsResponse = Http::acceptJson()
                ->timeout($timeout)
                ->withHeaders(['Cookie' => 'PHPSESSID=' . $phpsessid])
                ->get($mesAgentsUrl);
            if ($agentsResponse->successful()) {
                $agents = $agentsResponse->json('agents') ?? [];
            }
        } catch (\Throwable $e) {}

        try {
            $usinesResponse = Http::acceptJson()
                ->timeout($timeout)
                ->get('https://api.objetombrepegasus.online/api/camions/mes_usines.php');
            if ($usinesResponse->successful()) {
                $usines = $usinesResponse->json('usines') ?? [];
            }
        } catch (\Throwable $e) {}

        // Récupérer les chefs des chargeurs
        $chefChargeurs = \App\Models\ChefChargeur::orderBy('nom')->get();

        return view('fiches_sortie.index', [
            'fiches' => $fiches,
            'vehicules' => $vehicules,
            'ponts' => $ponts,
            'agents' => $agents,
            'usines' => $usines,
            'chefChargeurs' => $chefChargeurs,
            'external_error' => null,
        ]);
    }

    public function index(Request $request, int $vehiculeId)
    {
        $matricule = (string) $request->query('matricule', '');

        $depenses = Depense::where('vehicule_id', $vehiculeId)
            ->orderBy('date_depense', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        $displayMatricule = $matricule;
        if (!$displayMatricule) {
            $existingDepense = Depense::where('vehicule_id', $vehiculeId)->first();
            $displayMatricule = $existingDepense?->matricule_vehicule ?: '';
        }

        // Charger les ponts, agents et usines pour le modal fiche de sortie
        $timeout = 10;
        $ponts = [];
        $agents = [];
        $usines = [];

        try {
            $pontsResponse = Http::acceptJson()
                ->timeout($timeout)
                ->get('https://api.objetombrepegasus.online/api/camions/mes_ponts.php');
            if ($pontsResponse->successful()) {
                $ponts = $pontsResponse->json('ponts') ?? [];
            }
        } catch (\Throwable $e) {
            // Ignorer l'erreur
        }

        try {
            $agentsResponse = Http::acceptJson()
                ->timeout($timeout)
                ->get('https://api.objetombrepegasus.online/api/camions/mes_agents.php');
            if ($agentsResponse->successful()) {
                $agents = $agentsResponse->json('agents') ?? [];
            }
        } catch (\Throwable $e) {
            // Ignorer l'erreur
        }

        try {
            $usinesResponse = Http::acceptJson()
                ->timeout($timeout)
                ->get('https://api.objetombrepegasus.online/api/camions/mes_usines.php');
            if ($usinesResponse->successful()) {
                $usines = $usinesResponse->json('usines') ?? [];
            }
        } catch (\Throwable $e) {
            // Ignorer l'erreur
        }

        // Charger les chefs des chargeurs
        $chefChargeurs = \App\Models\ChefChargeur::orderBy('nom')->get();

        return view('depenses.index', [
            'depenses' => $depenses,
            'vehicule' => [
                'vehicules_id' => $vehiculeId,
                'matricule_vehicule' => $displayMatricule,
            ],
            'vehicule_id' => $vehiculeId,
            'ponts' => $ponts,
            'agents' => $agents,
            'usines' => $usines,
            'chefChargeurs' => $chefChargeurs,
            'external_error' => null,
        ]);
    }

    public function store(Request $request, int $vehiculeId)
    {
        $validated = $request->validate([
            'type_depense' => ['required', 'string', 'max:100'],
            'matricule_vehicule' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'montant' => ['required', 'numeric', 'min:0'],
            'date_depense' => ['required', 'date'],
        ]);

        Depense::create([
            'vehicule_id' => $vehiculeId,
            'matricule_vehicule' => $validated['matricule_vehicule'],
            'type_depense' => $validated['type_depense'],
            'description' => $validated['description'] ?? '',
            'montant' => $validated['montant'],
            'date_depense' => $validated['date_depense'],
        ]);

        return back()->with('success', 'Dépense enregistrée avec succès.');
    }

    public function ficheSortie(Request $request, int $vehiculeId)
    {
        $authUser = Auth::user();

        if (!$authUser || $authUser->role !== 'proprietaire') {
            return redirect()->route('vehicules.depenses', ['vehicule_id' => $vehiculeId])
                ->withErrors(['error' => "Accès réservé aux propriétaires."]);
        }

        $depenses = Depense::where('vehicule_id', $vehiculeId)
            ->orderBy('date_depense', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $matricule = '';
        $existingDepense = Depense::where('vehicule_id', $vehiculeId)->first();
        if ($existingDepense) {
            $matricule = $existingDepense->matricule_vehicule;
        }

        $totalDepenses = $depenses->sum('montant');

        // Récupérer les infos du pont et de l'agent sélectionnés
        $timeout = (int) config('services.external_auth.timeout', 10);
        $pont = null;
        $agent = null;

        $idPont = (int) $request->query('id_pont', 0);
        $idAgent = (int) $request->query('id_agent', 0);

        if ($idPont > 0) {
            try {
                $pontsResponse = Http::acceptJson()
                    ->timeout($timeout)
                    ->get(config('services.external_auth.mes_ponts_url'));
                if ($pontsResponse->successful()) {
                    $ponts = $pontsResponse->json('ponts') ?? [];
                    foreach ($ponts as $p) {
                        if ((int)$p['id_pont'] === $idPont) {
                            $pont = $p;
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Ignorer
            }
        }

        if ($idAgent > 0) {
            try {
                $agentsResponse = Http::acceptJson()
                    ->timeout($timeout)
                    ->get(config('services.external_auth.mes_agents_url'));
                if ($agentsResponse->successful()) {
                    $agents = $agentsResponse->json('agents') ?? [];
                    foreach ($agents as $a) {
                        if ((int)$a['id_agent'] === $idAgent) {
                            $agent = $a;
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Ignorer
            }
        }

        // Récupérer les tickets du véhicule pour le modal d'association
        $tickets = [];
        $ficheSortie = $request->query('fiche_id') ? FicheSortie::find($request->query('fiche_id')) : null;
        
        // Utiliser le matricule de la fiche de sortie si disponible
        if ($ficheSortie && $ficheSortie->matricule_vehicule) {
            $matricule = $ficheSortie->matricule_vehicule;
        }
        
        if ($ficheSortie && !$ficheSortie->id_ticket) {
            try {
                $phpsessid = session('external_auth.phpsessid', '');
                $ticketsResponse = Http::acceptJson()
                    ->timeout($timeout)
                    ->withHeaders(['Cookie' => 'PHPSESSID=' . $phpsessid])
                    ->get(config('services.external_auth.mes_tickets_url'));
                if ($ticketsResponse->successful()) {
                    $allTickets = $ticketsResponse->json('tickets') ?? [];
                    // Filtrer les tickets du véhicule par matricule ET par nom d'agent de la fiche
                    $agentNom = strtolower(trim($ficheSortie->nom_agent ?? ''));
                    $tickets = array_filter($allTickets, function($t) use ($matricule, $agentNom) {
                        $matchMatricule = ($t['matricule_vehicule'] ?? '') === $matricule;
                        // Comparer par nom d'agent (nom complet ou partiel)
                        $ticketAgentNom = strtolower(trim(($t['agent_nom'] ?? '') . ' ' . ($t['agent_prenom'] ?? '')));
                        if (empty($ticketAgentNom) || $ticketAgentNom === ' ') {
                            $ticketAgentNom = strtolower(trim($t['nom_agent'] ?? ''));
                        }
                        $matchAgent = empty($agentNom) || str_contains($ticketAgentNom, $agentNom) || str_contains($agentNom, $ticketAgentNom);
                        return $matchMatricule && $matchAgent;
                    });
                    $tickets = array_values($tickets);
                }
            } catch (\Throwable $e) {
                // Ignorer
            }
        }

        return view('depenses.fiche_sortie', [
            'depenses' => $depenses,
            'vehicule' => [
                'vehicules_id' => $vehiculeId,
                'matricule_vehicule' => $matricule,
            ],
            'vehicule_id' => $vehiculeId,
            'total_depenses' => $totalDepenses,
            'pont' => $pont,
            'agent' => $agent,
            'fiche_sortie' => $ficheSortie,
            'tickets' => $tickets,
        ]);
    }

    public function storeFicheSortie(Request $request, int $vehiculeId)
    {
        $validated = $request->validate([
            'id_pont' => ['required', 'integer'],
            'id_agent' => ['required', 'integer'],
            'date_chargement' => ['required', 'date'],
            'poids_pont' => ['nullable', 'numeric', 'min:0'],
            'usine' => ['nullable', 'string', 'max:255'],
            'id_chef_chargeur' => ['nullable', 'integer'],
            'carburant' => ['nullable', 'integer', 'min:0'],
            'frais_route' => ['nullable', 'integer', 'min:0'],
            'pont_display' => ['nullable', 'string'],
            'agent_display' => ['nullable', 'string'],
            'matricule_vehicule' => ['required', 'string', 'max:50'],
        ]);

        // Utiliser le matricule du formulaire
        $matricule = $validated['matricule_vehicule'];

        // Parser les infos du pont et de l'agent depuis le display
        $pontDisplay = $validated['pont_display'] ?? '';
        $agentDisplay = $validated['agent_display'] ?? '';

        // Extraire nom_pont et code_pont depuis "Nom Pont (CODE)"
        $nomPont = '';
        $codePont = '';
        if (preg_match('/^(.+)\s+\(([^)]+)\)$/', $pontDisplay, $matches)) {
            $nomPont = trim($matches[1]);
            $codePont = trim($matches[2]);
        }

        // Extraire nom_agent et numero_agent depuis "Nom Agent (NUMERO)"
        $nomAgent = '';
        $numeroAgent = '';
        if (preg_match('/^(.+)\s+\(([^)]+)\)$/', $agentDisplay, $matches)) {
            $nomAgent = trim($matches[1]);
            $numeroAgent = trim($matches[2]);
        }

        $ficheSortie = \App\Models\FicheSortie::create([
            'vehicule_id' => $vehiculeId,
            'matricule_vehicule' => $matricule,
            'id_pont' => $validated['id_pont'],
            'nom_pont' => $nomPont,
            'code_pont' => $codePont,
            'usine' => $validated['usine'] ?? null,
            'id_agent' => $validated['id_agent'],
            'nom_agent' => $nomAgent,
            'numero_agent' => $numeroAgent,
            'id_chef_chargeur' => $validated['id_chef_chargeur'] ?? null,
            'date_chargement' => $validated['date_chargement'],
            'poids_pont' => $validated['poids_pont'] ?? null,
            'carburant' => $validated['carburant'] ?? null,
            'frais_route' => $validated['frais_route'] ?? null,
        ]);

        // Réponse JSON pour les requêtes AJAX
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Fiche de sortie créée avec succès.',
                'fiche_id' => $ficheSortie->id,
            ]);
        }

        return redirect()->route('vehicules.fiche_sortie', [
            'vehicule_id' => $vehiculeId,
            'fiche_id' => $ficheSortie->id,
            'id_pont' => $validated['id_pont'],
            'id_agent' => $validated['id_agent'],
            'date_chargement' => $validated['date_chargement'],
            'poids_pont' => $validated['poids_pont'] ?? 0,
        ])->with('success', 'Fiche de sortie enregistrée avec succès.');
    }

    public function associerTicket(Request $request, int $ficheId)
    {
        $ficheSortie = FicheSortie::findOrFail($ficheId);

        $validated = $request->validate([
            'id_ticket' => ['required', 'integer'],
            'numero_ticket' => ['required', 'string', 'max:100'],
        ]);

        // Extraire le numero_ticket depuis le format "NUMERO - MATRICULE"
        $numeroTicket = $validated['numero_ticket'];
        if (str_contains($numeroTicket, ' - ')) {
            $parts = explode(' - ', $numeroTicket);
            $numeroTicket = trim($parts[0]);
        }

        $ficheSortie->update([
            'id_ticket' => $validated['id_ticket'],
            'numero_ticket' => $numeroTicket,
        ]);

        return redirect()->route('fiches_sortie.show', ['fiche_id' => $ficheSortie->id])
            ->with('success', 'Ticket associé avec succès.');
    }

    public function updatePrixTransport(Request $request, int $ficheId)
    {
        $authUser = Auth::user();

        if (!$authUser || $authUser->role !== 'proprietaire') {
            return back()->withErrors(['error' => "Accès réservé aux propriétaires."]);
        }

        $ficheSortie = FicheSortie::findOrFail($ficheId);

        $validated = $request->validate([
            'prix_unitaire_transport' => ['nullable', 'numeric', 'min:0'],
            'poids_unitaire_regime' => ['nullable', 'numeric', 'min:0'],
        ]);

        $ficheSortie->update([
            'prix_unitaire_transport' => $validated['prix_unitaire_transport'],
            'poids_unitaire_regime' => $validated['poids_unitaire_regime'],
        ]);

        return redirect()->route('tickets.index')->with('success', 'Valeurs mises à jour.');
    }

    public function storeFicheSortieFromList(Request $request)
    {
        $authUser = Auth::user();

        if (!$authUser || $authUser->role !== 'proprietaire') {
            return back()->withErrors(['error' => "Accès réservé aux propriétaires."]);
        }

        $validated = $request->validate([
            'vehicule_id' => ['required', 'integer'],
            'matricule_vehicule' => ['nullable', 'string', 'max:50'],
            'id_pont' => ['required', 'integer'],
            'id_agent' => ['required', 'integer'],
            'date_chargement' => ['required', 'date'],
            'date_dechargement' => ['nullable', 'date'],
            'poids_pont' => ['nullable', 'numeric', 'min:0'],
            'usine' => ['nullable', 'string', 'max:255'],
            'pont_display' => ['nullable', 'string'],
            'agent_display' => ['nullable', 'string'],
        ]);

        // Si matricule_vehicule est vide, récupérer depuis l'API
        $matricule = $validated['matricule_vehicule'] ?? '';
        if (empty($matricule)) {
            $mesCamionsUrl = (string) config('services.external_auth.mes_camions_url');
            $timeout = (int) config('services.external_auth.timeout', 10);
            $phpsessid = session('external_auth.phpsessid', '');
            try {
                $response = Http::acceptJson()
                    ->timeout($timeout)
                    ->withHeaders(['Cookie' => 'PHPSESSID=' . $phpsessid])
                    ->get($mesCamionsUrl);
                if ($response->successful()) {
                    $vehicules = $response->json('vehicules') ?? [];
                    foreach ($vehicules as $v) {
                        if (($v['id_vehicule'] ?? 0) == $validated['vehicule_id']) {
                            $matricule = $v['matricule_vehicule'] ?? '';
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {}
        }

        // Parser les infos du pont et de l'agent depuis le display
        $pontDisplay = $validated['pont_display'] ?? '';
        $agentDisplay = $validated['agent_display'] ?? '';

        // Extraire nom_pont et code_pont depuis "Nom Pont (CODE)"
        $nomPont = '';
        $codePont = '';
        if (preg_match('/^(.+)\s+\(([^)]+)\)$/', $pontDisplay, $matches)) {
            $nomPont = trim($matches[1]);
            $codePont = trim($matches[2]);
        }

        // Si pont_display est vide, récupérer depuis l'API
        if (empty($nomPont)) {
            $mesPontsUrl = (string) config('services.external_auth.mes_ponts_url');
            $timeout = (int) config('services.external_auth.timeout', 10);
            $phpsessid = session('external_auth.phpsessid', '');
            try {
                $pontsResponse = Http::acceptJson()
                    ->timeout($timeout)
                    ->withHeaders(['Cookie' => 'PHPSESSID=' . $phpsessid])
                    ->get($mesPontsUrl);
                if ($pontsResponse->successful()) {
                    $ponts = $pontsResponse->json('ponts') ?? [];
                    foreach ($ponts as $p) {
                        if (($p['id_pont'] ?? 0) == $validated['id_pont']) {
                            $nomPont = $p['nom_pont'] ?? '';
                            $codePont = $p['code_pont'] ?? '';
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {}
        }

        // Extraire nom_agent et numero_agent depuis "Nom Agent (NUMERO)"
        $nomAgent = '';
        $numeroAgent = '';
        if (preg_match('/^(.+)\s+\(([^)]+)\)$/', $agentDisplay, $matches)) {
            $nomAgent = trim($matches[1]);
            $numeroAgent = trim($matches[2]);
        }

        // Si agent_display est vide, récupérer depuis l'API
        if (empty($nomAgent)) {
            $mesAgentsUrl = (string) config('services.external_auth.mes_agents_url');
            $timeout = (int) config('services.external_auth.timeout', 10);
            $phpsessid = session('external_auth.phpsessid', '');
            try {
                $agentsResponse = Http::acceptJson()
                    ->timeout($timeout)
                    ->withHeaders(['Cookie' => 'PHPSESSID=' . $phpsessid])
                    ->get($mesAgentsUrl);
                if ($agentsResponse->successful()) {
                    $agents = $agentsResponse->json('agents') ?? [];
                    foreach ($agents as $a) {
                        if (($a['id_agent'] ?? 0) == $validated['id_agent']) {
                            $nomAgent = $a['nom_complet'] ?? (($a['nom_agent'] ?? '') . ' ' . ($a['prenom_agent'] ?? ''));
                            $numeroAgent = $a['numero_agent'] ?? '';
                            break;
                        }
                    }
                }
            } catch (\Throwable $e) {}
        }

        FicheSortie::create([
            'vehicule_id' => $validated['vehicule_id'],
            'matricule_vehicule' => $matricule,
            'id_pont' => $validated['id_pont'],
            'nom_pont' => $nomPont,
            'code_pont' => $codePont,
            'usine' => $validated['usine'] ?? null,
            'id_agent' => $validated['id_agent'],
            'nom_agent' => $nomAgent,
            'numero_agent' => $numeroAgent,
            'date_chargement' => $validated['date_chargement'],
            'date_dechargement' => $validated['date_dechargement'] ?? null,
            'poids_pont' => $validated['poids_pont'] ?? null,
            'id_ticket' => null,
            'numero_ticket' => null,
            'prix_unitaire_transport' => 0,
            'poids_unitaire_regime' => 0,
        ]);

        return redirect()->route('fiches_sortie.index')->with('success', 'Fiche de sortie créée avec succès.');
    }

    public function storeFromList(Request $request)
    {
        $validated = $request->validate([
            'vehicule_id' => ['required', 'integer'],
            'matricule_vehicule' => ['required', 'string', 'max:50'],
            'type_depense' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'montant' => ['required', 'numeric', 'min:0'],
            'date_depense' => ['required', 'date'],
        ]);

        Depense::create([
            'vehicule_id' => $validated['vehicule_id'],
            'matricule_vehicule' => $validated['matricule_vehicule'],
            'type_depense' => $validated['type_depense'],
            'description' => $validated['description'] ?? '',
            'montant' => $validated['montant'],
            'date_depense' => $validated['date_depense'],
        ]);

        return redirect()->route('depenses.liste')->with('success', 'Dépense enregistrée avec succès.');
    }

    public function showFicheSortie(int $ficheId)
    {
        $ficheSortie = FicheSortie::findOrFail($ficheId);

        // Récupérer le chef des chargeurs si assigné
        $chefChargeur = null;
        $paiementChargeur = null;
        $prixUnitaireChargeur = null;

        if ($ficheSortie->id_chef_chargeur) {
            $chefChargeur = \App\Models\ChefChargeur::find($ficheSortie->id_chef_chargeur);

            // Calculer le paiement chargeur si on a le poids et la date de chargement
            if ($chefChargeur && $ficheSortie->poids_pont && $ficheSortie->date_chargement) {
                // Trouver le prix unitaire applicable à la date de chargement
                $prixPeriode = \App\Models\ChefChargeurPrix::where('id_chef_chargeur', $chefChargeur->id)
                    ->where('date_debut', '<=', $ficheSortie->date_chargement)
                    ->where(function ($query) use ($ficheSortie) {
                        $query->whereNull('date_fin')
                              ->orWhere('date_fin', '>=', $ficheSortie->date_chargement);
                    })
                    ->first();

                if ($prixPeriode) {
                    $prixUnitaireChargeur = $prixPeriode->prix_unitaire;
                    $paiementChargeur = $prixUnitaireChargeur * (float) $ficheSortie->poids_pont;
                }
            }
        }

        return view('fiches_sortie.show', [
            'fiche' => $ficheSortie,
            'chefChargeur' => $chefChargeur,
            'paiementChargeur' => $paiementChargeur,
            'prixUnitaireChargeur' => $prixUnitaireChargeur,
        ]);
    }

    public function getTicketsConformesApi()
    {
        // Récupérer les tickets depuis l'API Unipalm
        $timeout = (int) config('services.external_auth.timeout', 10);
        $tickets = [];
        
        try {
            $response = Http::acceptJson()
                ->timeout($timeout)
                ->get('https://api.objetombrepegasus.online/api/camions/mes_tickets.php');
            
            if ($response->successful()) {
                $ticketsApi = $response->json('tickets') ?? [];
                // Formater les tickets pour le frontend
                foreach ($ticketsApi as $t) {
                    $tickets[] = [
                        'id_ticket' => $t['id_ticket'] ?? 0,
                        'numero_ticket' => $t['numero_ticket'] ?? '',
                        'matricule_vehicule' => $t['matricule_vehicule'] ?? '',
                        'date_ticket' => $t['date_ticket'] ?? '',
                        'agent_nom' => trim(($t['agent_nom'] ?? '') . ' ' . ($t['agent_prenom'] ?? '')),
                        'poids' => $t['poids'] ?? 0,
                    ];
                }
            }
        } catch (\Throwable $e) {}

        return response()->json($tickets);
    }

    public function updateFicheSortie(Request $request, int $ficheId)
    {
        $ficheSortie = FicheSortie::findOrFail($ficheId);

        $validated = $request->validate([
            'id_pont' => ['required', 'integer'],
            'id_agent' => ['required', 'integer'],
            'id_chef_chargeur' => ['nullable', 'integer'],
            'usine' => ['nullable', 'string', 'max:255'],
            'date_dechargement' => ['nullable', 'date'],
            'poids_pont' => ['nullable', 'numeric', 'min:0'],
            'carburant' => ['nullable', 'integer', 'min:0'],
            'frais_route' => ['nullable', 'integer', 'min:0'],
        ]);

        // Récupérer les infos du pont depuis l'API
        $timeout = 10;
        $nomPont = $ficheSortie->nom_pont;
        $codePont = $ficheSortie->code_pont;
        $nomAgent = $ficheSortie->nom_agent;
        $numeroAgent = $ficheSortie->numero_agent;

        try {
            $pontsResponse = Http::acceptJson()
                ->timeout($timeout)
                ->get('https://api.objetombrepegasus.online/api/camions/mes_ponts.php');
            if ($pontsResponse->successful()) {
                $ponts = $pontsResponse->json('ponts') ?? [];
                foreach ($ponts as $p) {
                    if (($p['id_pont'] ?? null) == $validated['id_pont']) {
                        $nomPont = $p['nom_pont'] ?? '';
                        $codePont = $p['code_pont'] ?? '';
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignorer l'erreur
        }

        try {
            $agentsResponse = Http::acceptJson()
                ->timeout($timeout)
                ->get('https://api.objetombrepegasus.online/api/camions/mes_agents.php');
            if ($agentsResponse->successful()) {
                $agents = $agentsResponse->json('agents') ?? [];
                foreach ($agents as $a) {
                    if (($a['id_agent'] ?? null) == $validated['id_agent']) {
                        $nomAgent = $a['nom_complet'] ?? (($a['nom_agent'] ?? '') . ' ' . ($a['prenom_agent'] ?? ''));
                        $numeroAgent = $a['numero_agent'] ?? '';
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignorer l'erreur
        }

        $ficheSortie->update([
            'id_pont' => $validated['id_pont'],
            'nom_pont' => $nomPont,
            'code_pont' => $codePont,
            'id_agent' => $validated['id_agent'],
            'nom_agent' => $nomAgent,
            'numero_agent' => $numeroAgent,
            'id_chef_chargeur' => $validated['id_chef_chargeur'] ?? null,
            'usine' => $validated['usine'] ?? null,
            'date_dechargement' => $validated['date_dechargement'] ?? null,
            'poids_pont' => $validated['poids_pont'] ?? null,
            'carburant' => $validated['carburant'] ?? null,
            'frais_route' => $validated['frais_route'] ?? null,
        ]);

        return redirect()->route('fiches_sortie.index')->with('success', 'Fiche de sortie modifiée avec succès.');
    }

    public function destroyFicheSortie(int $ficheId)
    {
        $ficheSortie = FicheSortie::findOrFail($ficheId);
        $ficheSortie->delete();

        return redirect()->route('fiches_sortie.index')->with('success', 'Fiche de sortie supprimée avec succès.');
    }
}
