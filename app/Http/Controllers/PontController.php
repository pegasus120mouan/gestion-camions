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
                ->withoutVerifying()
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

        // Calculer le stock disponible et le solde pour chaque pont
        foreach ($ponts as &$pont) {
            $idPont = $pont['id_pont'] ?? 0;
            
            // Trouver TOUS les stocks ouverts pour ce pont (un par parc)
            $stocksOuverts = Stock::where('id_pont', $idPont)
                ->where('type', 'entree')
                ->where('statut', 'ouvert')
                ->get();
            
            $totalStockDisponible = 0;
            
            foreach ($stocksOuverts as $stockOuvert) {
                $entrees = $stockOuvert->total_entrees;
                
                // Sorties liées à ce stock spécifique (via stock_id)
                $sorties = \App\Models\FicheSortie::where('stock_id', $stockOuvert->id)
                    ->whereNotNull('date_dechargement')
                    ->whereNotNull('poids_pont')
                    ->sum('poids_pont');
                
                $totalStockDisponible += max(0, $entrees - $sorties);
            }
            
            $pont['stock_disponible'] = $totalStockDisponible;

            // Calculer le solde (total approvisionnements - total dépenses stocks)
            $totalApprovisionnements = \App\Models\Approvisionnement::where('pont_id', $idPont)->sum('montant');
            $totalDepensesStocks = Stock::where('id_pont', $idPont)->where('type', 'entree')->sum('montant_total');
            $pont['solde'] = $totalApprovisionnements - $totalDepensesStocks;
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
                ->withoutVerifying()
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
        
        // Calculer les sorties réelles du pont (poids_pont des fiches de sortie déchargées)
        $totalSortiesFiches = \App\Models\FicheSortie::where('id_pont', $id_pont)
            ->whereNotNull('date_dechargement')
            ->whereNotNull('poids_pont')
            ->sum('poids_pont');
        
        // Total des sorties = sorties manuelles + sorties des fiches déchargées
        $totalSorties = $totalSortiesManuelles + $totalSortiesFiches;
        
        // Stock disponible = entrées - sorties totales
        $stockTotal = $totalEntrees;
        $stockDisponible = $totalEntrees - $totalSorties;
        
        $nbMouvements = $stocks->count();
        
        // Récupérer les fiches de sortie déchargées pour ce pont
        $fichesDechargees = \App\Models\FicheSortie::where('id_pont', $id_pont)
            ->whereNotNull('date_dechargement')
            ->whereNotNull('poids_pont')
            ->orderBy('date_dechargement', 'desc')
            ->get();

        // Calculer le solde (total approvisionnements - total dépenses stocks)
        $totalApprovisionnements = \App\Models\Approvisionnement::where('pont_id', $id_pont)->sum('montant');
        $totalDepensesStocks = Stock::where('id_pont', $id_pont)->where('type', 'entree')->sum('montant_total');
        $solde = $totalApprovisionnements - $totalDepensesStocks;

        return view('ponts.stock', [
            'pont' => $pont,
            'stocks' => $stocks,
            'stockTotal' => $stockTotal,
            'totalSorties' => $totalSorties,
            'stockDisponible' => $stockDisponible,
            'fichesDechargees' => $fichesDechargees,
            'nbMouvements' => $nbMouvements,
            'solde' => $solde,
            'external_error' => null,
            'parcs' => \App\Models\Parc::where('id_pont', $id_pont)->where('statut', 'actif')->get(),
        ]);
    }

    public function storeStock(Request $request, int $id_pont)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:entree,sortie'],
            'parc_id' => ['required', 'exists:parcs,id'],
            'quantite' => ['required', 'numeric', 'min:0'],
            'prix_unitaire' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
        ]);

        // Récupérer les infos du pont
        $mesPontsUrl = (string) config('services.external_auth.mes_ponts_url');
        $timeout = (int) config('services.external_auth.timeout', 10);
        $pont = null;

        try {
            $response = Http::acceptJson()->withoutVerifying()->timeout($timeout)->get($mesPontsUrl);
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

        // Récupérer le parc
        $parc = \App\Models\Parc::find($validated['parc_id']);
        if (!$parc || $parc->id_pont != $id_pont) {
            return redirect()->route('ponts.stock', ['id_pont' => $id_pont])->withErrors(['error' => 'Parc invalide pour ce pont.']);
        }

        // Vérifier s'il y a déjà un stock ouvert pour ce parc spécifique
        $stockOuvertParc = Stock::where('id_pont', $id_pont)
            ->where('parc_id', $parc->id)
            ->where('type', 'entree')
            ->where('statut', 'ouvert')
            ->exists();
        
        if ($stockOuvertParc) {
            return redirect()->route('ponts.stock', ['id_pont' => $id_pont])->withErrors(['error' => 'Un stock est déjà ouvert pour le parc "' . $parc->nom . '". Fermez-le avant d\'en créer un nouveau.']);
        }

        // Calculer le montant total (prix_unitaire * quantité)
        $prixUnitaire = $validated['prix_unitaire'] ?? 0;
        $montantTotal = $prixUnitaire * $validated['quantite'];

        // Vérifier si le solde est suffisant
        $soldeActuel = \App\Models\Approvisionnement::where('pont_id', $id_pont)->sum('montant');
        $totalDepenses = Stock::where('id_pont', $id_pont)->where('type', 'entree')->sum('montant_total');
        $soldeDisponible = $soldeActuel - $totalDepenses;

        if ($montantTotal > 0 && $montantTotal > $soldeDisponible) {
            return redirect()->route('ponts.stock', ['id_pont' => $id_pont])
                ->withErrors(['error' => 'Solde insuffisant. Solde disponible: ' . number_format($soldeDisponible, 0, ',', ' ') . ' FCFA, Montant requis: ' . number_format($montantTotal, 0, ',', ' ') . ' FCFA']);
        }

        $codePont = $pont['code_pont'] ?? 'PONT';
        $codeStock = Stock::generateCodeStock($id_pont, $codePont);

        Stock::create([
            'id_pont' => $id_pont,
            'parc_id' => $parc->id,
            'nom_parc' => $parc->nom,
            'code_pont' => $codePont,
            'nom_pont' => $pont['nom_pont'] ?? '',
            'type' => 'entree',
            'quantite' => $validated['quantite'],
            'prix_unitaire' => $prixUnitaire,
            'montant_total' => $montantTotal,
            'date_mouvement' => $validated['date'],
            'code_stock' => $codeStock,
            'statut' => 'ouvert',
        ]);

        return redirect()->route('ponts.stock', ['id_pont' => $id_pont])->with('success', 'Stock créé avec le code: ' . $codeStock);
    }

    public function fermerStock(Request $request, int $id_pont, int $stock_id)
    {
        $stock = Stock::where('id', $stock_id)->where('id_pont', $id_pont)->where('type', 'entree')->first();
        
        if (!$stock) {
            return redirect()->route('ponts.stock', ['id_pont' => $id_pont])->withErrors(['error' => 'Stock non trouvé.']);
        }

        if ($stock->isFerme()) {
            return redirect()->route('ponts.stock', ['id_pont' => $id_pont])->withErrors(['error' => 'Ce stock est déjà fermé.']);
        }

        $stock->update([
            'statut' => 'ferme',
            'date_fermeture' => now(),
        ]);

        return redirect()->route('ponts.stock', ['id_pont' => $id_pont])->with('success', 'Stock fermé avec succès.');
    }

    public function deleteStock(int $id_pont, int $stock_id)
    {
        $stock = Stock::where('id', $stock_id)->where('id_pont', $id_pont)->first();
        
        if ($stock) {
            // Détacher les fiches de sortie (stock_id nullable, conservées dans l'historique)
            \App\Models\FicheSortie::where('stock_id', $stock->id)->update(['stock_id' => null]);

            // Supprimer les entrées supplémentaires liées
            $stock->entreesStock()->delete();

            $stock->delete();
            return redirect()->route('ponts.stock', ['id_pont' => $id_pont])->with('success', 'Stock supprimé avec succès.');
        }

        return redirect()->route('ponts.stock', ['id_pont' => $id_pont])->withErrors(['error' => 'Stock non trouvé.']);
    }

    public function addEntreeStock(Request $request, int $id_pont, int $stock_id)
    {
        $validated = $request->validate([
            'quantite' => ['required', 'numeric', 'min:0'],
            'date_entree' => ['required', 'date'],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ]);

        $stock = Stock::where('id', $stock_id)->where('id_pont', $id_pont)->where('type', 'entree')->first();
        
        if (!$stock) {
            return redirect()->route('ponts.stock', ['id_pont' => $id_pont])->withErrors(['error' => 'Stock non trouvé.']);
        }

        if ($stock->isFerme()) {
            return redirect()->route('ponts.stock', ['id_pont' => $id_pont])->withErrors(['error' => 'Impossible d\'ajouter une entrée à un stock fermé.']);
        }

        \App\Models\EntreeStock::create([
            'stock_id' => $stock->id,
            'quantite' => $validated['quantite'],
            'date_entree' => $validated['date_entree'],
            'commentaire' => $validated['commentaire'] ?? null,
        ]);

        return redirect()->route('ponts.stock', ['id_pont' => $id_pont])->with('success', 'Entrée de stock ajoutée avec succès.');
    }

    public function updateEntreeStock(Request $request, int $id_pont, int $stock_id, int $entree_id)
    {
        $validated = $request->validate([
            'quantite' => ['required', 'numeric', 'min:0'],
            'date_entree' => ['required', 'date'],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ]);

        $stock = Stock::where('id', $stock_id)->where('id_pont', $id_pont)->where('type', 'entree')->first();
        
        if (!$stock || $stock->isFerme()) {
            return redirect()->route('ponts.stock', ['id_pont' => $id_pont])->withErrors(['error' => 'Impossible de modifier cette entrée.']);
        }

        $entree = \App\Models\EntreeStock::where('id', $entree_id)->where('stock_id', $stock_id)->first();
        
        if (!$entree) {
            return redirect()->route('ponts.stock', ['id_pont' => $id_pont])->withErrors(['error' => 'Entrée non trouvée.']);
        }

        $entree->update([
            'quantite' => $validated['quantite'],
            'date_entree' => $validated['date_entree'],
            'commentaire' => $validated['commentaire'] ?? null,
        ]);

        return redirect()->route('ponts.stock', ['id_pont' => $id_pont])->with('success', 'Entrée de stock modifiée avec succès.');
    }

    public function deleteEntreeStock(int $id_pont, int $stock_id, int $entree_id)
    {
        $stock = Stock::where('id', $stock_id)->where('id_pont', $id_pont)->where('type', 'entree')->first();
        
        if (!$stock || $stock->isFerme()) {
            return redirect()->route('ponts.stock', ['id_pont' => $id_pont])->withErrors(['error' => 'Impossible de supprimer cette entrée.']);
        }

        $entree = \App\Models\EntreeStock::where('id', $entree_id)->where('stock_id', $stock_id)->first();
        
        if (!$entree) {
            return redirect()->route('ponts.stock', ['id_pont' => $id_pont])->withErrors(['error' => 'Entrée non trouvée.']);
        }

        $entree->delete();

        return redirect()->route('ponts.stock', ['id_pont' => $id_pont])->with('success', 'Entrée de stock supprimée avec succès.');
    }

    public function sorties(Request $request)
    {
        $mesPontsUrl = (string) config('services.external_auth.mes_ponts_url');
        $timeout = (int) config('services.external_auth.timeout', 10);

        // Récupérer les ponts
        $ponts = [];
        try {
            $response = Http::acceptJson()->withoutVerifying()->timeout($timeout)->get($mesPontsUrl);
            if ($response->successful()) {
                $ponts = $response->json('ponts') ?? [];
            }
        } catch (\Throwable $e) {}

        // Récupérer toutes les fiches de sortie déchargées (indépendamment du pont)
        $fichesDechargees = \App\Models\FicheSortie::whereNotNull('date_dechargement')
            ->whereNotNull('poids_pont')
            ->orderBy('date_dechargement', 'desc')
            ->get();

        // Grouper les sorties par pont pour le résumé
        $sortiesParPont = [];
        foreach ($fichesDechargees as $fiche) {
            $idPont = $fiche->id_pont;
            $nomPont = $fiche->nom_pont;

            if (!isset($sortiesParPont[$idPont])) {
                $sortiesParPont[$idPont] = [
                    'id_pont' => $idPont,
                    'nom_pont' => $nomPont,
                    'total_poids' => 0,
                    'nb_fiches' => 0,
                ];
            }

            $sortiesParPont[$idPont]['total_poids'] += (float) $fiche->poids_pont;
            $sortiesParPont[$idPont]['nb_fiches']++;
        }

        // Calculer le stock actuel pour chaque pont
        foreach ($sortiesParPont as &$sortie) {
            $idPont = $sortie['id_pont'];
            $totalEntrees = Stock::where('id_pont', $idPont)->where('type', 'entree')->sum('quantite');
            $totalSortiesManuelles = Stock::where('id_pont', $idPont)->where('type', 'sortie')->sum('quantite');
            $sortie['stock_initial'] = $totalEntrees - $totalSortiesManuelles;
            $sortie['stock_disponible'] = $sortie['stock_initial'] - $sortie['total_poids'];
        }
        unset($sortie);

        return view('ponts.sorties', [
            'sortiesParPont' => array_values($sortiesParPont),
            'fichesDechargees' => $fichesDechargees,
            'ponts' => $ponts,
            'external_error' => null,
        ]);
    }
}
