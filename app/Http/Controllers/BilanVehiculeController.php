<?php

namespace App\Http\Controllers;

use App\Models\CodeTransporteur;
use App\Models\CodeTransporteurVehicule;
use App\Models\FicheSortie;
use Illuminate\Http\Request;

class BilanVehiculeController extends Controller
{
    public function index()
    {
        // Récupérer toutes les catégories (code transporteurs) avec leurs véhicules
        $categories = CodeTransporteur::with('vehicules')->orderBy('nom')->get();

        // Pour chaque catégorie, calculer les statistiques
        foreach ($categories as $categorie) {
            $vehiculeIds = $categorie->vehicules->pluck('vehicule_id')->toArray();
            
            // Nombre de véhicules
            $categorie->nb_vehicules = $categorie->vehicules->count();
            
            // Total des fiches de sortie
            $categorie->nb_fiches = FicheSortie::whereIn('vehicule_id', $vehiculeIds)->count();
            
            // Total carburant
            $categorie->total_carburant = FicheSortie::whereIn('vehicule_id', $vehiculeIds)->sum('carburant') ?? 0;
            
            // Total frais route
            $categorie->total_frais_route = FicheSortie::whereIn('vehicule_id', $vehiculeIds)->sum('frais_route') ?? 0;
            
            // Total poids livré
            $categorie->total_poids = FicheSortie::whereIn('vehicule_id', $vehiculeIds)
                ->whereNotNull('poids_pont')
                ->sum('poids_pont') ?? 0;

            // Total montant camion (revenus)
            $categorie->total_montant_camion = FicheSortie::whereIn('vehicule_id', $vehiculeIds)
                ->whereNotNull('montant_camion')
                ->sum('montant_camion') ?? 0;

            // Pour chaque véhicule de la catégorie, calculer son bilan
            foreach ($categorie->vehicules as $vehicule) {
                $vehicule->nb_fiches = FicheSortie::where('vehicule_id', $vehicule->vehicule_id)->count();
                $vehicule->total_carburant = FicheSortie::where('vehicule_id', $vehicule->vehicule_id)->sum('carburant') ?? 0;
                $vehicule->total_frais_route = FicheSortie::where('vehicule_id', $vehicule->vehicule_id)->sum('frais_route') ?? 0;
                $vehicule->total_poids = FicheSortie::where('vehicule_id', $vehicule->vehicule_id)
                    ->whereNotNull('poids_pont')
                    ->sum('poids_pont') ?? 0;
                $vehicule->total_montant_camion = FicheSortie::where('vehicule_id', $vehicule->vehicule_id)
                    ->whereNotNull('montant_camion')
                    ->sum('montant_camion') ?? 0;
                $vehicule->total_depenses = $vehicule->total_carburant + $vehicule->total_frais_route;
                $vehicule->marge = $vehicule->total_montant_camion - $vehicule->total_depenses;
            }
        }

        return view('bilan-vehicule.index', [
            'categories' => $categories,
        ]);
    }

    public function show(int $vehicule_id)
    {
        // Récupérer les infos du véhicule
        $vehicule = CodeTransporteurVehicule::where('vehicule_id', $vehicule_id)->first();
        
        if (!$vehicule) {
            return redirect()->route('bilan-vehicule.index')->withErrors(['error' => 'Véhicule non trouvé.']);
        }

        // Récupérer toutes les fiches de sortie du véhicule
        $fiches = FicheSortie::where('vehicule_id', $vehicule_id)
            ->orderBy('date_chargement', 'desc')
            ->get();

        // Calculer les totaux
        $totalCarburant = $fiches->sum('carburant');
        $totalFraisRoute = $fiches->sum('frais_route');
        $totalPoids = $fiches->whereNotNull('poids_pont')->sum('poids_pont');
        $totalMontantCamion = $fiches->whereNotNull('montant_camion')->sum('montant_camion');
        $totalDepenses = $totalCarburant + $totalFraisRoute;
        $marge = $totalMontantCamion - $totalDepenses;

        return view('bilan-vehicule.show', [
            'vehicule' => $vehicule,
            'fiches' => $fiches,
            'totalCarburant' => $totalCarburant,
            'totalFraisRoute' => $totalFraisRoute,
            'totalPoids' => $totalPoids,
            'totalMontantCamion' => $totalMontantCamion,
            'totalDepenses' => $totalDepenses,
            'marge' => $marge,
        ]);
    }
}
