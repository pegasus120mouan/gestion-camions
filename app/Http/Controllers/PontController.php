<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PontController extends Controller
{
    public function index(Request $request)
    {
        $mesPontsUrl = (string) config('services.external_auth.mes_ponts_url');
        $timeout = (int) config('services.external_auth.timeout', 10);

        try {
            $response = Http::acceptJson()
                ->timeout($timeout)
                ->get($mesPontsUrl);
        } catch (\Throwable $e) {
            return view('ponts.index', [
                'ponts' => [],
                'external_error' => "Impossible de joindre le service ponts.",
            ]);
        }

        if (!$response->successful()) {
            $message = (string) ($response->json('error') ?? 'Erreur API.');

            return view('ponts.index', [
                'ponts' => [],
                'external_error' => $message,
            ]);
        }

        $ponts = $response->json('ponts');
        if (!is_array($ponts)) {
            $ponts = [];
        }

        // Récupérer les tickets pour calculer les sorties (poids usine)
        $mesTicketsUrl = (string) config('services.external_auth.mes_tickets_url');
        $phpsessid = (string) $request->session()->get('external_auth.phpsessid', '');
        $tickets = [];
        try {
            $ticketsResponse = Http::acceptJson()
                ->timeout($timeout)
                ->withHeaders(['Cookie' => 'PHPSESSID=' . $phpsessid])
                ->get($mesTicketsUrl);
            if ($ticketsResponse->successful()) {
                $tickets = $ticketsResponse->json('tickets') ?? [];
            }
        } catch (\Throwable $e) {}

        // Récupérer les fiches de sortie pour associer les tickets aux ponts
        $ticketIds = array_column($tickets, 'id_ticket');
        $sortiesParPont = [];
        if (!empty($ticketIds)) {
            $fiches = \App\Models\FicheSortie::whereIn('id_ticket', $ticketIds)->get();
            foreach ($fiches as $fiche) {
                $idPont = $fiche->id_pont;
                $idTicket = $fiche->id_ticket;
                // Trouver le poids usine du ticket
                foreach ($tickets as $ticket) {
                    if (($ticket['id_ticket'] ?? null) == $idTicket) {
                        $poidsUsine = (float) ($ticket['poids'] ?? 0);
                        if (!isset($sortiesParPont[$idPont])) {
                            $sortiesParPont[$idPont] = 0;
                        }
                        $sortiesParPont[$idPont] += $poidsUsine;
                        break;
                    }
                }
            }
        }

        // Calculer le stock disponible pour chaque pont
        foreach ($ponts as &$pont) {
            $idPont = $pont['id_pont'] ?? 0;
            $totalEntrees = Stock::where('id_pont', $idPont)->where('type', 'entree')->sum('quantite');
            $totalSortiesManuelles = Stock::where('id_pont', $idPont)->where('type', 'sortie')->sum('quantite');
            $totalSortiesTickets = $sortiesParPont[$idPont] ?? 0;
            $totalSorties = $totalSortiesManuelles + $totalSortiesTickets;
            // Stock disponible = 0 si sorties >= entrées
            if ($totalSorties >= $totalEntrees) {
                $pont['stock_disponible'] = 0;
            } else {
                $pont['stock_disponible'] = $totalEntrees - $totalSorties;
            }
        }
        unset($pont);

        return view('ponts.index', [
            'ponts' => $ponts,
            'external_error' => null,
        ]);
    }

    public function stock(Request $request, int $id_pont)
    {
        $mesPontsUrl = (string) config('services.external_auth.mes_ponts_url');
        $timeout = (int) config('services.external_auth.timeout', 10);

        // Récupérer les infos du pont
        $pont = null;
        try {
            $response = Http::acceptJson()
                ->timeout($timeout)
                ->get($mesPontsUrl);
            
            if ($response->successful()) {
                $ponts = $response->json('ponts') ?? [];
                foreach ($ponts as $p) {
                    if (($p['id_pont'] ?? 0) == $id_pont) {
                        $pont = $p;
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {
            return view('ponts.stock', [
                'pont' => null,
                'stocks' => [],
                'external_error' => "Impossible de joindre le service ponts.",
            ]);
        }

        if (!$pont) {
            return redirect()->route('ponts.index')->withErrors(['error' => 'Pont non trouvé.']);
        }

        // Récupérer les stocks du pont depuis la base locale
        $stocks = Stock::where('id_pont', $id_pont)
            ->orderBy('date_mouvement', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Calculer les totaux
        $totalEntrees = Stock::where('id_pont', $id_pont)->where('type', 'entree')->sum('quantite');
        $totalSortiesManuelles = Stock::where('id_pont', $id_pont)->where('type', 'sortie')->sum('quantite');
        
        // Calculer les sorties réelles du pont (poids usine des tickets associés aux fiches de sortie)
        $fichesIds = \App\Models\FicheSortie::where('id_pont', $id_pont)->pluck('id_ticket')->filter()->toArray();
        $totalSortiesPont = 0;
        
        if (!empty($fichesIds)) {
            // Récupérer les tickets depuis l'API pour avoir le poids usine
            $mesTicketsUrl = (string) config('services.external_auth.mes_tickets_url');
            $timeout = (int) config('services.external_auth.timeout', 10);
            $phpsessid = (string) $request->session()->get('external_auth.phpsessid', '');
            
            try {
                $ticketsResponse = Http::acceptJson()
                    ->timeout($timeout)
                    ->withHeaders(['Cookie' => 'PHPSESSID=' . $phpsessid])
                    ->get($mesTicketsUrl);
                if ($ticketsResponse->successful()) {
                    $tickets = $ticketsResponse->json('tickets') ?? [];
                    foreach ($tickets as $ticket) {
                        if (in_array($ticket['id_ticket'] ?? 0, $fichesIds)) {
                            $totalSortiesPont += (float) ($ticket['poids'] ?? 0);
                        }
                    }
                }
            } catch (\Throwable $e) {}
        }
        
        // Stock initial = entrées - sorties manuelles
        $stockTotal = $totalEntrees - $totalSortiesManuelles;
        // Stock disponible = stock initial - sorties du pont (poids usine)
        $stockDisponible = $stockTotal - $totalSortiesPont;
        
        $nbMouvements = $stocks->count();

        return view('ponts.stock', [
            'pont' => $pont,
            'stocks' => $stocks,
            'stockTotal' => $stockTotal,
            'totalEntrees' => $totalSortiesPont,
            'totalSorties' => $stockDisponible,
            'nbMouvements' => $nbMouvements,
            'external_error' => null,
        ]);
    }

    public function storeStock(Request $request, int $id_pont)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:entree,sortie'],
            'quantite' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
        ]);

        // Récupérer les infos du pont
        $mesPontsUrl = (string) config('services.external_auth.mes_ponts_url');
        $timeout = (int) config('services.external_auth.timeout', 10);
        $pont = null;

        try {
            $response = Http::acceptJson()->timeout($timeout)->get($mesPontsUrl);
            if ($response->successful()) {
                $ponts = $response->json('ponts') ?? [];
                foreach ($ponts as $p) {
                    if (($p['id_pont'] ?? 0) == $id_pont) {
                        $pont = $p;
                        break;
                    }
                }
            }
        } catch (\Throwable $e) {}

        Stock::create([
            'id_pont' => $id_pont,
            'code_pont' => $pont['code_pont'] ?? '',
            'nom_pont' => $pont['nom_pont'] ?? '',
            'type' => 'entree',
            'quantite' => $validated['quantite'],
            'date_mouvement' => $validated['date'],
        ]);

        return redirect()->route('ponts.stock', ['id_pont' => $id_pont])->with('success', 'Mouvement de stock enregistré.');
    }

    public function deleteStock(int $id_pont, int $stock_id)
    {
        $stock = Stock::where('id', $stock_id)->where('id_pont', $id_pont)->first();
        
        if ($stock) {
            $stock->delete();
            return redirect()->route('ponts.stock', ['id_pont' => $id_pont])->with('success', 'Mouvement supprimé.');
        }

        return redirect()->route('ponts.stock', ['id_pont' => $id_pont])->withErrors(['error' => 'Mouvement non trouvé.']);
    }

    public function sorties(Request $request)
    {
        $mesTicketsUrl = (string) config('services.external_auth.mes_tickets_url');
        $mesPontsUrl = (string) config('services.external_auth.mes_ponts_url');
        $timeout = (int) config('services.external_auth.timeout', 10);
        $phpsessid = (string) $request->session()->get('external_auth.phpsessid', '');

        // Récupérer les ponts
        $ponts = [];
        try {
            $response = Http::acceptJson()->timeout($timeout)->get($mesPontsUrl);
            if ($response->successful()) {
                $ponts = $response->json('ponts') ?? [];
            }
        } catch (\Throwable $e) {}

        // Récupérer les tickets
        $tickets = [];
        try {
            $response = Http::acceptJson()
                ->timeout($timeout)
                ->withHeaders(['Cookie' => 'PHPSESSID=' . $phpsessid])
                ->get($mesTicketsUrl);
            if ($response->successful()) {
                $tickets = $response->json('tickets') ?? [];
            }
        } catch (\Throwable $e) {}

        // Récupérer les fiches de sortie pour avoir l'origine (nom du pont)
        $ticketIds = array_column($tickets, 'id_ticket');
        $fichesSortie = [];
        if (!empty($ticketIds)) {
            $fiches = \App\Models\FicheSortie::whereIn('id_ticket', $ticketIds)->get()->keyBy('id_ticket');
            foreach ($fiches as $idTicket => $fiche) {
                $fichesSortie[$idTicket] = [
                    'id_pont' => $fiche->id_pont,
                    'nom_pont' => $fiche->nom_pont,
                    'poids_pont' => $fiche->poids_pont,
                ];
            }
        }

        // Grouper les sorties par pont
        $sortiesParPont = [];
        foreach ($tickets as $ticket) {
            $idTicket = $ticket['id_ticket'] ?? null;
            $poidsUsine = (float) ($ticket['poids'] ?? 0);
            
            if (!$idTicket || !isset($fichesSortie[$idTicket])) {
                continue;
            }

            $fiche = $fichesSortie[$idTicket];
            $idPont = $fiche['id_pont'];
            $nomPont = $fiche['nom_pont'];

            if (!isset($sortiesParPont[$idPont])) {
                $sortiesParPont[$idPont] = [
                    'id_pont' => $idPont,
                    'nom_pont' => $nomPont,
                    'total_poids_usine' => 0,
                    'nb_tickets' => 0,
                    'tickets' => [],
                ];
            }

            $sortiesParPont[$idPont]['total_poids_usine'] += $poidsUsine;
            $sortiesParPont[$idPont]['nb_tickets']++;
            $sortiesParPont[$idPont]['tickets'][] = [
                'id_ticket' => $idTicket,
                'numero_ticket' => $ticket['numero_ticket'] ?? '',
                'matricule_vehicule' => $ticket['matricule_vehicule'] ?? '',
                'poids_usine' => $poidsUsine,
                'date_dechargement' => $ticket['date_dechargement'] ?? '',
            ];
        }

        // Calculer le stock actuel et la sortie pour chaque pont
        foreach ($sortiesParPont as &$sortie) {
            $idPont = $sortie['id_pont'];
            $totalEntrees = Stock::where('id_pont', $idPont)->where('type', 'entree')->sum('quantite');
            $totalSorties = Stock::where('id_pont', $idPont)->where('type', 'sortie')->sum('quantite');
            $sortie['stock_actuel'] = $totalEntrees - $totalSorties;
            $sortie['stock_apres_sortie'] = $sortie['stock_actuel'] - $sortie['total_poids_usine'];
        }
        unset($sortie);

        return view('ponts.sorties', [
            'sortiesParPont' => array_values($sortiesParPont),
            'ponts' => $ponts,
            'external_error' => null,
        ]);
    }
}
