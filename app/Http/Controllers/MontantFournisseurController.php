<?php

namespace App\Http\Controllers;

use App\Models\Fournisseur;
use App\Models\PaiementFournisseur;
use App\Models\Depense;
use Illuminate\Http\Request;

class MontantFournisseurController extends Controller
{
    public function index()
    {
        $fournisseurs = Fournisseur::with(['service', 'paiements'])->orderBy('nom')->get();

        // Calculer les montants pour chaque fournisseur
        $fournisseursData = $fournisseurs->map(function ($fournisseur) {
            // Montant dû = somme des dépenses où description = nom du fournisseur
            $montantDu = Depense::where('description', $fournisseur->nom)->sum('montant');
            
            // Montant payé = somme des paiements
            $montantPaye = $fournisseur->paiements->sum('montant');
            
            // Reste à payer
            $resteAPayer = $montantDu - $montantPaye;

            return [
                'fournisseur' => $fournisseur,
                'montant_du' => $montantDu,
                'montant_paye' => $montantPaye,
                'reste_a_payer' => $resteAPayer,
            ];
        });

        return view('gestion_financiere.montant_fournisseur', [
            'fournisseursData' => $fournisseursData,
        ]);
    }

    public function storePaiement(Request $request)
    {
        $validated = $request->validate([
            'fournisseur_id' => 'required|exists:fournisseurs,id',
            'montant' => 'required|numeric|min:0',
            'date_paiement' => 'required|date',
            'mode_paiement' => 'required|string',
            'reference' => 'nullable|string|max:255',
            'commentaire' => 'nullable|string',
        ]);

        PaiementFournisseur::create($validated);

        return back()->with('success', 'Paiement enregistré avec succès.');
    }
}
