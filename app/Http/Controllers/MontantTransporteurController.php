<?php

namespace App\Http\Controllers;

use App\Models\CodeTransporteur;
use App\Models\CodeTransporteurVehicule;
use App\Models\FicheSortie;
use Illuminate\Http\Request;

class MontantTransporteurController extends Controller
{
    public function index(Request $request)
    {
        // Uniquement le transporteur "Autre"
        $transporteur = CodeTransporteur::where('nom', 'Autre')->first();

        if (!$transporteur) {
            return view('gestion_financiere.montant_transporteur', [
                'transporteursData' => collect(),
                'vehicules' => [],
            ]);
        }

        // Récupérer les matricules des véhicules liés à ce transporteur
        $matricules = CodeTransporteurVehicule::where('code_transporteur_id', $transporteur->id)
            ->pluck('matricule_vehicule')
            ->toArray();

        // Liste des véhicules pour le filtre
        $vehicules = $matricules;

        // Récupérer les fiches de sortie de ces véhicules avec filtres
        $fichesSortie = collect();
        if (!empty($matricules)) {
            $query = FicheSortie::whereIn('matricule_vehicule', $matricules);

            // Filtre par véhicule
            if ($request->filled('vehicule')) {
                $query->where('matricule_vehicule', $request->vehicule);
            }

            // Filtre par date début
            if ($request->filled('date_debut')) {
                $query->whereDate('date_chargement', '>=', $request->date_debut);
            }

            // Filtre par date fin
            if ($request->filled('date_fin')) {
                $query->whereDate('date_chargement', '<=', $request->date_fin);
            }

            $fichesSortie = $query->orderBy('date_chargement', 'desc')->get();
        }

        // Calculer Montant Global = somme de (Poids * PU) pour chaque fiche
        $montantGlobal = $fichesSortie->sum(function ($fiche) {
            $poids = $fiche->poids_pont ?? 0;
            $pu = $fiche->prix_unitaire_transport ?? 0;
            return $poids * $pu;
        });

        // Calculer l'Avance totale (Carburant + Frais Route + Dépenses)
        $totalAvance = $fichesSortie->sum(function ($fiche) {
            $carburant = $fiche->carburant ?? 0;
            $fraisRoute = $fiche->frais_route ?? 0;
            
            // Dépenses liées à cette fiche
            $depenses = \App\Models\Depense::where('matricule_vehicule', $fiche->matricule_vehicule)
                ->whereDate('date_depense', '>=', $fiche->date_chargement)
                ->whereDate('date_depense', '<=', $fiche->date_dechargement ?? $fiche->date_chargement)
                ->sum('montant');
            
            return $carburant + $fraisRoute + $depenses;
        });

        // Montant Payé = Avance + Montant Payé (paiements effectués)
        $montantPayeTransporteur = $fichesSortie->sum('montant_paye_transporteur');
        $montantPaye = $totalAvance + $montantPayeTransporteur;
        
        // Reste à Payer = Montant Global - Montant Payé (Avance + Paiements)
        $resteAPayer = $montantGlobal - $montantPaye;

        return view('gestion_financiere.montant_transporteur', [
            'fichesSortie' => $fichesSortie,
            'montantDu' => $montantGlobal,
            'montantPaye' => $montantPaye,
            'resteAPayer' => $resteAPayer,
            'transporteurNom' => 'Autre',
            'vehicules' => $vehicules,
        ]);
    }

    public function indexOld()
    {
        $transporteurs = CodeTransporteur::orderBy('nom')->get();

        // Calculer les montants pour chaque transporteur
        $transporteursData = $transporteurs->map(function ($transporteur) {
            // Récupérer les matricules des véhicules liés à ce transporteur
            $matricules = CodeTransporteurVehicule::where('code_transporteur_id', $transporteur->id)
                ->pluck('matricule_vehicule')
                ->toArray();
            
            // Montant dû = somme des frais_route des fiches de sortie de ces véhicules
            $montantDu = 0;
            $nbFiches = 0;
            if (!empty($matricules)) {
                $montantDu = FicheSortie::whereIn('matricule_vehicule', $matricules)->sum('frais_route');
                $nbFiches = FicheSortie::whereIn('matricule_vehicule', $matricules)->count();
            }
            
            // Pour l'instant, pas de système de paiement pour les transporteurs
            $montantPaye = 0;
            
            // Reste à payer
            $resteAPayer = $montantDu - $montantPaye;

            return [
                'transporteur' => $transporteur,
                'montant_du' => $montantDu,
                'montant_paye' => $montantPaye,
                'reste_a_payer' => $resteAPayer,
                'nb_fiches' => $nbFiches,
            ];
        });

        return view('gestion_financiere.montant_transporteur', [
            'transporteursData' => $transporteursData,
        ]);
    }

    public function show($nom)
    {
        $transporteur = CodeTransporteur::where('nom', $nom)->first();

        // Récupérer les matricules des véhicules liés à ce transporteur
        $matricules = [];
        if ($transporteur) {
            $matricules = CodeTransporteurVehicule::where('code_transporteur_id', $transporteur->id)
                ->pluck('matricule_vehicule')
                ->toArray();
        }

        // Récupérer les fiches de sortie de ces véhicules
        $fichesSortie = collect();
        if (!empty($matricules)) {
            $fichesSortie = FicheSortie::whereIn('matricule_vehicule', $matricules)
                ->orderBy('date_chargement', 'desc')
                ->get();
        }

        $montantDu = $fichesSortie->sum('frais_route');
        $montantPaye = 0;
        $resteAPayer = $montantDu - $montantPaye;

        return view('gestion_financiere.transporteur_detail', [
            'transporteur' => $transporteur,
            'transporteurNom' => $nom,
            'fichesSortie' => $fichesSortie,
            'montantDu' => $montantDu,
            'montantPaye' => $montantPaye,
            'resteAPayer' => $resteAPayer,
        ]);
    }

    public function updatePU(Request $request, int $ficheId)
    {
        $validated = $request->validate([
            'prix_unitaire' => ['required', 'numeric', 'min:0'],
        ]);

        $fiche = FicheSortie::findOrFail($ficheId);
        $fiche->update([
            'prix_unitaire_transport' => $validated['prix_unitaire'],
        ]);

        return redirect()->route('gestionfinanciere.montant_transporteur')
            ->with('success', 'Prix unitaire mis à jour avec succès.');
    }

    public function storePaiement(Request $request, int $ficheId)
    {
        // Nettoyer le montant (enlever les espaces)
        $montant = str_replace(' ', '', $request->input('montant'));
        $request->merge(['montant' => $montant]);

        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:1'],
            'date_paiement' => ['required', 'date'],
            'observation' => ['nullable', 'string'],
        ]);

        $fiche = FicheSortie::findOrFail($ficheId);
        
        // Ajouter le montant au montant déjà payé
        $nouveauMontantPaye = ($fiche->montant_paye_transporteur ?? 0) + $validated['montant'];
        
        $fiche->update([
            'montant_paye_transporteur' => $nouveauMontantPaye,
        ]);

        return redirect()->route('gestionfinanciere.montant_transporteur')
            ->with('success', 'Paiement de ' . number_format($validated['montant'], 0, ',', ' ') . ' FCFA enregistré avec succès.');
    }
}
