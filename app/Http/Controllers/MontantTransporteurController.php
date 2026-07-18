<?php

namespace App\Http\Controllers;

use App\Models\BordereauTransporteur;
use App\Models\Depense;
use App\Models\FicheSortie;
use App\Models\PaiementTransporteur;
use App\Models\Transporteur;
use App\Services\BordereauTransporteurService;
use App\Services\TicketTransporteurFicheService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class MontantTransporteurController extends Controller
{
    public function __construct(
        private BordereauTransporteurService $bordereauTransporteur,
    ) {}

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

        $ticketFicheService = app(TicketTransporteurFicheService::class);
        if ($request->boolean('sync')) {
            $ticketFicheService->reconcilierFichesPourTransporteur($transporteur);
        }

        $fichesQuery = FicheSortie::query()
            ->where('transporteur_id', $transporteur->id);

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

        $fichesSortie = $fichesSortie->filter(function (FicheSortie $fiche) use ($transporteur) {
            return (int) $fiche->transporteur_id === (int) $transporteur->id;
        })->values();

        $bordereaux = BordereauTransporteur::query()
            ->where('transporteur_id', $transporteur->id)
            ->orderByDesc('date_generation')
            ->orderByDesc('id')
            ->get();

        $fichesHorsBordereau = $fichesSortie->filter(function (FicheSortie $fiche) {
            return $fiche->bordereau_transporteur_id === null;
        })->values();

        $montants = $this->calculerMontantsTransporteur($transporteur, $fichesSortie, $bordereaux);
        $paiementsGestion = $transporteur->paiementsGestion()->orderBy('date_paiement', 'desc')->get();
        $avancesTransporteur = $transporteur->avances()->get();

        return view('gestion_financiere.transporteur_detail', array_merge([
            'transporteur' => $transporteur,
            'fichesSortie' => $fichesHorsBordereau,
            'bordereaux' => $bordereaux,
            'exempleNumeroBordereau' => $this->bordereauTransporteur->exempleNumero($transporteur->code),
            'vehicules' => $vehicules,
            'paiementsGestion' => $paiementsGestion,
            'montantPayeGestion' => $paiementsGestion->sum('montant'),
            'avancesTransporteur' => $avancesTransporteur,
            'montantAvancesTransporteur' => $avancesTransporteur->sum('montant'),
            'ticketFicheService' => $ticketFicheService,
        ], $montants));
    }

    public function storePaiementGestion(Request $request, Transporteur $transporteur)
    {
        if ($request->has('montant')) {
            $request->merge([
                'montant' => preg_replace('/\s+/', '', (string) $request->input('montant')),
            ]);
        }

        $validated = $request->validate([
            'montant' => ['required', 'integer', 'min:1'],
            'date_paiement' => ['required', 'date'],
            'mode_paiement' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ]);

        $transporteur->paiementsGestion()->create($validated);

        return redirect()->back()->with(
            'success',
            'Paiement de '.number_format((int) $validated['montant'], 0, ',', ' ').' FCFA enregistré avec succès.'
        );
    }

    public function storeAvance(Request $request, Transporteur $transporteur)
    {
        if ($request->has('montant')) {
            $request->merge([
                'montant' => preg_replace('/\s+/', '', (string) $request->input('montant')),
            ]);
        }

        $validated = $request->validate([
            'montant' => ['required', 'integer', 'min:1'],
            'date_avance' => ['required', 'date'],
            'mode_paiement' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ]);

        $transporteur->avances()->create($validated);

        return redirect()->route('gestionfinanciere.transporteur.show', $transporteur)
            ->with(
                'success',
                'Avance de '.number_format((int) $validated['montant'], 0, ',', ' ')
                .' FCFA enregistrée avec succès.'
            );
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

        return FicheSortie::query()
            ->where('transporteur_id', $transporteur->id)
            ->get();
    }

    private function calculerMontantsTransporteur(Transporteur $transporteur, $fiches = null, $bordereaux = null): array
    {
        $fiches = $this->queryFichesTransporteur($transporteur, $fiches);
        $bordereaux = $bordereaux ?? collect();

        $montantDu = $fiches->sum(fn ($fiche) => $this->calculerMontantGlobalFiche($fiche));
        $totalAvance = $fiches->sum(fn ($fiche) => $this->calculerAvanceFiche($fiche));
        $montantPayeFiches = $fiches
            ->filter(fn ($fiche) => $fiche->bordereau_transporteur_id === null)
            ->sum('montant_paye_transporteur');
        $montantPayeBordereaux = (float) $bordereaux->sum('montant_paye');
        $montantPayeGestion = (float) $transporteur->paiementsGestion()->sum('montant');
        $montantAvancesTransporteur = (float) $transporteur->avances()->sum('montant');
        $montantPayeSansAvances = $totalAvance
            + $montantPayeFiches
            + $montantPayeBordereaux
            + $montantPayeGestion;
        $montantPaye = $montantPayeSansAvances + $montantAvancesTransporteur;
        $resteAPayer = $montantDu - $montantPaye;

        return [
            'montant_du' => (int) $montantDu,
            'montant_paye' => (int) $montantPaye,
            'reste_a_payer' => (int) $resteAPayer,
            'montantDu' => (int) $montantDu,
            'montantPaye' => (int) $montantPaye,
            'montantPayeSansAvances' => (int) $montantPayeSansAvances,
            'resteAPayer' => (int) $resteAPayer,
            'montantAvancesTransporteur' => (int) $montantAvancesTransporteur,
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
        if ($fiche->transporteur_id) {
            $transporteur = Transporteur::query()->find($fiche->transporteur_id);
            if ($transporteur) {
                return route('gestionfinanciere.transporteur.show', $transporteur);
            }
        }

        $transporteur = Transporteur::whereHas('vehicules', function ($query) use ($fiche) {
            $query->where('matricule_vehicule', $fiche->matricule_vehicule);
        })->first();

        if ($transporteur) {
            return route('gestionfinanciere.transporteur.show', $transporteur);
        }

        return route('gestionfinanciere.montant_transporteur');
    }

    public function fichesEligiblesBordereau(Request $request, Transporteur $transporteur)
    {
        $validated = $request->validate([
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
        ]);

        $fiches = $this->bordereauTransporteur->fichesEligibles(
            (int) $transporteur->id,
            $validated['date_debut'],
            $validated['date_fin']
        );

        return response()->json([
            'fiches' => $fiches,
            'total_montant' => (int) collect($fiches)->sum('montant'),
            'total_poids' => (float) collect($fiches)->sum('poids'),
        ]);
    }

    public function storeBordereau(Request $request, Transporteur $transporteur)
    {
        $validated = $request->validate([
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'fiche_ids' => ['required', 'array', 'min:1'],
            'fiche_ids.*' => ['integer'],
        ]);

        $fichesData = $this->bordereauTransporteur->construireFichesData(
            (int) $transporteur->id,
            $validated['fiche_ids']
        );

        if ($fichesData === []) {
            return back()->withErrors(['error' => 'Aucune fiche valide sélectionnée (prix unitaire manquant, déjà bordereau ou introuvable).']);
        }

        $nomComplet = trim($transporteur->nom . ' ' . $transporteur->prenoms);

        $bordereau = BordereauTransporteur::create([
            'transporteur_id' => $transporteur->id,
            'numero' => $this->bordereauTransporteur->genererNumero($transporteur->code),
            'transporteur_nom' => $nomComplet,
            'transporteur_code' => $transporteur->code,
            'date_generation' => now()->toDateString(),
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'],
            'montant_total' => collect($fichesData)->sum('montant'),
            'poids_total' => collect($fichesData)->sum('poids'),
            'fiches_data' => $fichesData,
        ]);

        $this->bordereauTransporteur->assignerFichesAuBordereau(
            $bordereau,
            collect($fichesData)->pluck('fiche_id')->all()
        );

        return redirect()->route('gestionfinanciere.transporteur.bordereau.show', [
            'transporteur' => $transporteur->id,
            'id' => $bordereau->id,
        ])->with('success', 'Bordereau ' . $bordereau->numero . ' généré avec succès.');
    }

    public function showBordereau(Transporteur $transporteur, int $id)
    {
        $bordereau = BordereauTransporteur::query()
            ->where('transporteur_id', $transporteur->id)
            ->findOrFail($id);

        $groupesUsine = $this->bordereauTransporteur->grouperParUsine($bordereau->fiches_data ?? []);

        return view('gestion_financiere.bordereau_transporteur_show', [
            'transporteur' => $transporteur,
            'bordereau' => $bordereau,
            'groupesUsine' => $groupesUsine,
        ]);
    }

    public function exportBordereauPdf(Transporteur $transporteur, int $id)
    {
        $bordereau = BordereauTransporteur::query()
            ->where('transporteur_id', $transporteur->id)
            ->findOrFail($id);

        $groupesUsine = $this->bordereauTransporteur->grouperParUsine($bordereau->fiches_data ?? []);
        $logoPath = public_path('assets/img/logo.png');

        $pdf = Pdf::loadView('gestion_financiere.bordereau_transporteur_pdf', [
            'transporteur' => $transporteur,
            'bordereau' => $bordereau,
            'groupesUsine' => $groupesUsine,
            'logoPath' => file_exists($logoPath) ? $logoPath : null,
            'transporteurNom' => trim($transporteur->nom . ' ' . $transporteur->prenoms),
            'dateCreation' => ($bordereau->created_at ?? now())->format('d/m/Y \à H:i'),
        ])->setPaper('a4', 'portrait');

        $filename = 'bordereau_' . preg_replace('/[^a-zA-Z0-9_-]+/', '_', $bordereau->numero) . '.pdf';

        return $pdf->stream($filename);
    }

    public function destroyBordereau(Transporteur $transporteur, int $id)
    {
        $bordereau = BordereauTransporteur::query()
            ->where('transporteur_id', $transporteur->id)
            ->findOrFail($id);

        $this->bordereauTransporteur->libererFichesDuBordereau($bordereau);
        $bordereau->delete();

        return redirect()->route('gestionfinanciere.transporteur.show', $transporteur)
            ->with('success', 'Bordereau supprimé.');
    }

    public function storePaiementBordereau(Request $request, Transporteur $transporteur, int $id)
    {
        $montant = str_replace(' ', '', $request->input('montant', ''));
        $request->merge(['montant' => $montant]);

        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:1'],
            'date_paiement' => ['required', 'date'],
            'observation' => ['nullable', 'string', 'max:500'],
        ]);

        $bordereau = BordereauTransporteur::query()
            ->where('transporteur_id', $transporteur->id)
            ->findOrFail($id);

        PaiementTransporteur::create([
            'id_bordereau' => $bordereau->id,
            'matricule_vehicule' => '',
            'montant' => $validated['montant'],
            'date_paiement' => $validated['date_paiement'],
            'observation' => $validated['observation'] ?? ('Paiement bordereau ' . $bordereau->numero),
        ]);

        $bordereau->update([
            'montant_paye' => (float) $bordereau->montant_paye + $validated['montant'],
        ]);

        return back()->with('success', 'Paiement de ' . number_format($validated['montant'], 0, ',', ' ') . ' FCFA enregistré pour le bordereau ' . $bordereau->numero . '.');
    }
}
