<?php

namespace App\Http\Controllers;

use App\Models\Depense;
use App\Models\FicheSortie;
use App\Models\PaiementTransporteur;
use App\Models\Transporteur;
use App\Services\TicketTransporteurFicheService;
use Illuminate\Http\Request;

class MontantTransporteurController extends Controller
{
    public function index()
    {
        $transporteurs = Transporteur::withCount('vehicules')->orderBy('nom')->get();

        $data = [];
        foreach ($transporteurs as $transporteur) {
            $montants = $this->calculerMontantsTransporteur($transporteur);

            $data[] = array_merge(['transporteur' => $transporteur], $montants);
        }

        return view('gestion_financiere.montant_transporteur', [
            'data' => $data,
        ]);
    }

    public function show(Request $request, Transporteur $transporteur)
    {
        $transporteur->load('vehicules');
        $matricules = $this->getMatricules($transporteur);
        $vehicules = $matricules;

        $fichesSortie = collect();
        $fichesQuery = FicheSortie::query()
            ->where(function ($query) use ($transporteur, $matricules) {
                $query->where('transporteur_id', $transporteur->id);
                if (!empty($matricules)) {
                    $query->orWhereIn('matricule_vehicule', $matricules);
                }
            });

        if ($request->filled('vehicule')) {
            $fichesQuery->where('matricule_vehicule', $request->vehicule);
        }

        if ($request->filled('date_debut')) {
            $fichesQuery->whereDate('date_chargement', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $fichesQuery->whereDate('date_chargement', '<=', $request->date_fin);
        }

        $fichesSortie = $fichesQuery->orderBy('date_chargement', 'desc')->get();

        $ticketFicheService = app(TicketTransporteurFicheService::class);
        $fichesSortie = $fichesSortie->map(function (FicheSortie $fiche) use ($ticketFicheService) {
            return $ticketFicheService->synchroniserDonneesTicketSurFiche($fiche);
        });

        $montants = $this->calculerMontantsTransporteur($transporteur, $fichesSortie);
        $paiementsGestion = $transporteur->paiementsGestion()->orderBy('date_paiement', 'desc')->get();

        return view('gestion_financiere.transporteur_detail', array_merge([
            'transporteur' => $transporteur,
            'fichesSortie' => $fichesSortie,
            'vehicules' => $vehicules,
            'paiementsGestion' => $paiementsGestion,
            'montantPayeGestion' => $paiementsGestion->sum('montant'),
            'ticketFicheService' => $ticketFicheService,
        ], $montants));
    }

    public function storePaiementGestion(Request $request, Transporteur $transporteur)
    {
        $validated = $request->validate([
            'montant' => ['required', 'integer', 'min:1'],
            'date_paiement' => ['required', 'date'],
            'mode_paiement' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ]);

        $transporteur->paiementsGestion()->create($validated);

        return redirect()->back()->with('success', 'Paiement enregistré avec succès.');
    }

    public function updatePU(Request $request, int $ficheId)
    {
        $validated = $request->validate([
            'prix_unitaire' => ['required', 'numeric', 'min:0'],
        ]);

        $fiche = FicheSortie::findOrFail($ficheId);

        if (!$fiche->transporteur_id) {
            $transporteurId = \App\Models\TransporteurVehicule::query()
                ->where('matricule_vehicule', $fiche->matricule_vehicule)
                ->value('transporteur_id');

            if (!$transporteurId) {
                return redirect()->back()->with('error', 'Cette fiche n\'est pas rattachée à un transporteur.');
            }

            $fiche->update(['transporteur_id' => $transporteurId]);
        }

        $fiche->update([
            'prix_unitaire_transport' => $validated['prix_unitaire'],
        ]);

        return redirect($this->redirectUrlPourFiche($fiche))
            ->with('success', 'Prix unitaire enregistré avec succès.');
    }

    public function storePaiement(Request $request, int $ficheId)
    {
        $montant = str_replace(' ', '', $request->input('montant'));
        $request->merge(['montant' => $montant]);

        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:1'],
            'date_paiement' => ['required', 'date'],
            'observation' => ['nullable', 'string'],
        ]);

        $fiche = FicheSortie::findOrFail($ficheId);

        PaiementTransporteur::create([
            'fiche_sortie_id' => $ficheId,
            'matricule_vehicule' => $fiche->matricule_vehicule,
            'montant' => $validated['montant'],
            'date_paiement' => $validated['date_paiement'],
            'observation' => $validated['observation'] ?? null,
        ]);

        $nouveauMontantPaye = ($fiche->montant_paye_transporteur ?? 0) + $validated['montant'];

        $fiche->update([
            'montant_paye_transporteur' => $nouveauMontantPaye,
        ]);

        return redirect($this->redirectUrlPourFiche($fiche))
            ->with('success', 'Paiement de ' . number_format($validated['montant'], 0, ',', ' ') . ' FCFA enregistré avec succès.');
    }

    public function historiquePaiements(Request $request, Transporteur $transporteur)
    {
        $vehicules = $this->getMatricules($transporteur);

        $query = PaiementTransporteur::whereIn('matricule_vehicule', $vehicules)
            ->orderBy('date_paiement', 'desc');

        if ($request->filled('vehicule')) {
            $query->where('matricule_vehicule', $request->vehicule);
        }

        return response()->json([
            'paiements' => $query->get(),
            'vehicules' => $vehicules,
        ]);
    }

    public function showVehicule(string $matricule)
    {
        $fichesSortie = FicheSortie::where('matricule_vehicule', $matricule)
            ->orderBy('date_chargement', 'desc')
            ->get();

        $montantGlobal = $fichesSortie->sum(fn ($fiche) => $this->calculerMontantGlobalFiche($fiche));
        $totalAvance = $fichesSortie->sum(fn ($fiche) => $this->calculerAvanceFiche($fiche));
        $montantPayeTransporteur = $fichesSortie->sum('montant_paye_transporteur');
        $montantPaye = $totalAvance + $montantPayeTransporteur;
        $resteAPayer = $montantGlobal - $montantPaye;

        $paiements = PaiementTransporteur::where('matricule_vehicule', $matricule)
            ->orderBy('date_paiement', 'desc')
            ->get();

        $transporteur = Transporteur::whereHas('vehicules', function ($query) use ($matricule) {
            $query->where('matricule_vehicule', $matricule);
        })->first();

        return view('gestion_financiere.vehicule_transporteur', [
            'matricule' => $matricule,
            'transporteur' => $transporteur,
            'fichesSortie' => $fichesSortie,
            'totalFiches' => $fichesSortie->count(),
            'montantGlobal' => $montantGlobal,
            'totalAvance' => $totalAvance,
            'montantPaye' => $montantPaye,
            'resteAPayer' => $resteAPayer,
            'paiements' => $paiements,
        ]);
    }

    private function getMatricules(Transporteur $transporteur): array
    {
        return $transporteur->vehicules()->pluck('matricule_vehicule')->filter()->unique()->values()->toArray();
    }

    private function queryFichesTransporteur(Transporteur $transporteur, $fiches = null)
    {
        if ($fiches !== null) {
            return $fiches;
        }

        $matricules = $this->getMatricules($transporteur);

        return FicheSortie::query()
            ->where(function ($query) use ($transporteur, $matricules) {
                $query->where('transporteur_id', $transporteur->id);
                if (!empty($matricules)) {
                    $query->orWhereIn('matricule_vehicule', $matricules);
                }
            })
            ->get();
    }

    private function calculerMontantsTransporteur(Transporteur $transporteur, $fiches = null): array
    {
        $fiches = $this->queryFichesTransporteur($transporteur, $fiches);

        $montantDu = $fiches->sum(fn ($fiche) => $this->calculerMontantGlobalFiche($fiche));
        $totalAvance = $fiches->sum(fn ($fiche) => $this->calculerAvanceFiche($fiche));
        $montantPayeFiches = $fiches->sum('montant_paye_transporteur');
        $montantPayeGestion = $transporteur->paiementsGestion()->sum('montant');
        $montantPaye = $totalAvance + $montantPayeFiches + $montantPayeGestion;
        $resteAPayer = $montantDu - $montantPaye;

        return [
            'montant_du' => (int) $montantDu,
            'montant_paye' => (int) $montantPaye,
            'reste_a_payer' => (int) $resteAPayer,
            'montantDu' => (int) $montantDu,
            'montantPaye' => (int) $montantPaye,
            'resteAPayer' => (int) $resteAPayer,
        ];
    }

    private function calculerMontantGlobalFiche(FicheSortie $fiche): float
    {
        $poids = app(TicketTransporteurFicheService::class)->poidsEffectif($fiche);
        $pu = $fiche->prix_unitaire_transport ?? 0;

        return $poids * $pu;
    }

    private function calculerAvanceFiche(FicheSortie $fiche): float
    {
        $depenses = Depense::where('matricule_vehicule', $fiche->matricule_vehicule)
            ->whereDate('date_depense', '>=', $fiche->date_chargement)
            ->whereDate('date_depense', '<=', $fiche->date_dechargement ?? $fiche->date_chargement)
            ->sum('montant');

        return ($fiche->carburant ?? 0) + ($fiche->frais_route ?? 0) + $depenses;
    }

    private function redirectUrlPourFiche(FicheSortie $fiche): string
    {
        $transporteur = Transporteur::whereHas('vehicules', function ($query) use ($fiche) {
            $query->where('matricule_vehicule', $fiche->matricule_vehicule);
        })->first();

        if ($transporteur) {
            return route('gestionfinanciere.transporteur.show', $transporteur);
        }

        return route('gestionfinanciere.montant_transporteur');
    }
}
