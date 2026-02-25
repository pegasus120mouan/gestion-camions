<?php

namespace App\Http\Controllers;

use App\Models\ChefChargeur;
use App\Models\FicheSortie;
use App\Models\PaiementChefChargeur;
use App\Models\ChefChargeurPrix;
use Illuminate\Http\Request;

class MontantChefChargeurController extends Controller
{
    public function index()
    {
        $chefChargeurs = ChefChargeur::orderBy('nom')->get();

        $data = [];
        foreach ($chefChargeurs as $chef) {
            // Calculer le montant dû (somme des paiements chargeur des fiches de sortie)
            $montantDu = $this->calculerMontantDu($chef);

            // Calculer le montant payé
            $montantPaye = $chef->paiements()->sum('montant');

            // Reste à payer
            $resteAPayer = $montantDu - $montantPaye;

            $data[] = [
                'chef' => $chef,
                'montant_du' => $montantDu,
                'montant_paye' => $montantPaye,
                'reste_a_payer' => $resteAPayer,
            ];
        }

        return view('gestion_financiere.montant_chef_chargeur', [
            'data' => $data,
        ]);
    }

    private function calculerMontantDu(ChefChargeur $chef): int
    {
        $total = 0;

        // Récupérer toutes les fiches de sortie de ce chef
        $fiches = FicheSortie::where('id_chef_chargeur', $chef->id)
            ->whereNotNull('poids_pont')
            ->whereNotNull('date_chargement')
            ->get();

        foreach ($fiches as $fiche) {
            // Trouver le prix unitaire applicable à la date de chargement
            $prixPeriode = ChefChargeurPrix::where('id_chef_chargeur', $chef->id)
                ->where('date_debut', '<=', $fiche->date_chargement)
                ->where(function ($query) use ($fiche) {
                    $query->whereNull('date_fin')
                          ->orWhere('date_fin', '>=', $fiche->date_chargement);
                })
                ->first();

            if ($prixPeriode) {
                $total += $prixPeriode->prix_unitaire * (float) $fiche->poids_pont;
            }
        }

        return (int) $total;
    }

    public function storePaiement(Request $request, ChefChargeur $chefChargeur)
    {
        $validated = $request->validate([
            'montant' => ['required', 'integer', 'min:1'],
            'date_paiement' => ['required', 'date'],
            'mode_paiement' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ]);

        $chefChargeur->paiements()->create($validated);

        return redirect()->route('gestionfinanciere.montant_chef_chargeur')
            ->with('success', 'Paiement enregistré avec succès.');
    }
}
