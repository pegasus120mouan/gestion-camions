<?php

namespace App\Http\Controllers;

use App\Models\CodeTransporteur;
use App\Models\CodeTransporteurVehicule;
use App\Models\FicheSortie;
use Illuminate\Http\Request;

class MontantTransporteurController extends Controller
{
    public function index()
    {
        // Uniquement le transporteur "Autre"
        $transporteur = CodeTransporteur::where('nom', 'Autre')->first();

        if (!$transporteur) {
            return view('gestion_financiere.montant_transporteur', [
                'transporteursData' => collect(),
            ]);
        }

        // Récupérer les matricules des véhicules liés à ce transporteur
        $matricules = CodeTransporteurVehicule::where('code_transporteur_id', $transporteur->id)
            ->pluck('matricule_vehicule')
            ->toArray();

        // Récupérer les fiches de sortie de ces véhicules
        $fichesSortie = collect();
        if (!empty($matricules)) {
            $fichesSortie = FicheSortie::whereIn('matricule_vehicule', $matricules)
                ->orderBy('date_chargement', 'desc')
                ->get();
        }

        // Calculer Montant Dû = somme de (Poids * PU) pour chaque fiche
        $montantDu = $fichesSortie->sum(function ($fiche) {
            $poids = $fiche->poids_pont ?? 0;
            $pu = $fiche->prix_unitaire_transport ?? 0;
            return $poids * $pu;
        });
        $montantPaye = $fichesSortie->sum('montant_paye_transporteur');
        $resteAPayer = $montantDu - $montantPaye;

        return view('gestion_financiere.montant_transporteur', [
            'fichesSortie' => $fichesSortie,
            'montantDu' => $montantDu,
            'montantPaye' => $montantPaye,
            'resteAPayer' => $resteAPayer,
            'transporteurNom' => 'Autre',
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
