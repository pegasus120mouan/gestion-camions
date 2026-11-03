<?php

namespace App\Http\Controllers;

use App\Models\FicheSortie;
use App\Models\Produit;
use App\Models\Stock;
use App\Services\MesAgentsService;
use App\Services\TransfertGroupeService;
use App\Services\UsinesParProduitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class SortiesGroupeesController extends Controller
{
    public function __construct(
        private TransfertGroupeService $transfertGroupe,
        private MesAgentsService $mesAgents,
    ) {}

    public function index(Request $request)
    {
        $query = FicheSortie::query()
            ->where('type_fiche', FicheSortie::TYPE_GROUPEE)
            ->withCount('etapesTransfertGroupe')
            ->orderByDesc('date_chargement')
            ->orderByDesc('id');

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $query->where(function ($builder) use ($q) {
                $builder->where('numero_fiche', 'like', '%' . $q . '%')
                    ->orWhere('matricule_vehicule', 'like', '%' . $q . '%')
                    ->orWhere('nom_agent', 'like', '%' . $q . '%')
                    ->orWhere('numero_agent', 'like', '%' . $q . '%')
                    ->orWhere('nom_produit', 'like', '%' . $q . '%')
                    ->orWhere('usine', 'like', '%' . $q . '%');
            });
        }

        if ($request->filled('statut')) {
            $statut = (string) $request->input('statut');
            if (in_array($statut, [FicheSortie::STATUT_GROUPE_EN_COURS, FicheSortie::STATUT_GROUPE_CLOTUREE], true)) {
                $query->where('statut_groupe', $statut);
            }
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('date_chargement', '>=', $request->input('date_debut'));
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_chargement', '<=', $request->input('date_fin'));
        }

        $fiches = $query->paginate(20)->withQueryString();

        return view('sorties_groupees.index', [
            'fiches' => $fiches,
            'onglet' => 'fiches',
        ]);
    }

    public function show(Request $request, int $ficheId)
    {
        $fiche = FicheSortie::query()
            ->with('etapesTransfertGroupe')
            ->findOrFail($ficheId);

        if (! $fiche->estGroupee()) {
            abort(404);
        }

        $agents = [];
        try {
            $agents = $this->mesAgents->fetchAllAgents([], $request);
        } catch (\Throwable $e) {
            // ignore
        }

        $ponts = [];
        $depenseController = app(DepenseController::class);
        try {
            $pontsResponse = Http::acceptJson()
                ->withoutVerifying()
                ->timeout(10)
                ->get('https://api.objetombrepegasus.online/api/camions/mes_ponts.php');
            if ($pontsResponse->successful()) {
                $ponts = $depenseController->annoterPontsGerablePublic($pontsResponse->json('ponts') ?? []);
            }
        } catch (\Throwable $e) {
            // ignore
        }

        $agentsPontsMap = $depenseController->agentsPontsMapPour($ponts, $agents);

        $usinesParProduit = app(UsinesParProduitService::class)->usinesParProduitPourSelect();
        $usines = $usinesParProduit[(int) $fiche->produit_id]
            ?? $usinesParProduit[(string) $fiche->produit_id]
            ?? [];

        $stockLocalisation = null;
        $stockActuelChaine = null;
        if ($fiche->localisation_type === FicheSortie::LOCALISATION_PARC && $fiche->localisation_stock_id) {
            $stockLocalisation = Stock::query()->find($fiche->localisation_stock_id);
            if ($stockLocalisation) {
                $stockActuelChaine = $this->transfertGroupe->calculerStockActuelChaine($stockLocalisation);
            }
        }

        return view('sorties_groupees.show', [
            'fiche' => $fiche,
            'etapes' => $fiche->etapesTransfertGroupe,
            'agents' => $agents,
            'ponts' => $ponts,
            'agentsPontsMap' => $agentsPontsMap,
            'usines' => $usines,
            'produits' => Produit::orderBy('nom')->get(),
            'stockLocalisation' => $stockLocalisation,
            'stockActuelChaine' => $stockActuelChaine,
        ]);
    }

    public function stocksPontProduit(Request $request)
    {
        $validated = $request->validate([
            'id_pont' => ['required', 'integer'],
            'produit_id' => ['required', 'integer', 'exists:produits,id'],
        ]);

        $stocks = $this->transfertGroupe->stocksOuvertsPourPontProduit(
            (int) $validated['id_pont'],
            (int) $validated['produit_id']
        );

        return response()->json([
            'stocks' => $stocks,
        ]);
    }

    public function transfererVersPont(Request $request, int $ficheId)
    {
        $fiche = FicheSortie::query()->findOrFail($ficheId);
        if (! $fiche->estGroupee()) {
            abort(404);
        }

        $validated = $request->validate([
            'id_agent' => ['required', 'integer'],
            'agent_display' => ['nullable', 'string', 'max:255'],
            'stock_id' => ['required', 'integer', 'exists:stocks,id'],
            'date_etape' => ['required', 'date'],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ]);

        [$nomAgent, $numeroAgent] = $this->parseAgentDisplay($validated['agent_display'] ?? '');

        $stock = Stock::query()->findOrFail((int) $validated['stock_id']);
        $entreesAvant = (float) $stock->total_entrees;

        try {
            $etape = $this->transfertGroupe->transfererVersPont($fiche, [
                'id_agent' => (int) $validated['id_agent'],
                'nom_agent' => $nomAgent,
                'numero_agent' => $numeroAgent,
                'date_etape' => $validated['date_etape'],
                'commentaire' => $validated['commentaire'] ?? null,
            ], $stock);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }

        $stock->refresh();
        $entreesApres = (float) $stock->total_entrees;
        $poids = (float) $etape->poids;

        return redirect()
            ->route('sorties_groupees.show', $fiche->id)
            ->with(
                'success',
                sprintf(
                    'Transfert enregistré : %s kg (stock actuel source) ajoutés sur %s (%s). Stock entrée : %s → %s kg.',
                    number_format($poids, 0, ',', ' '),
                    $stock->nom_parc ?? 'parc',
                    $stock->nom_pont ?? 'pont',
                    number_format($entreesAvant, 0, ',', ' '),
                    number_format($entreesApres, 0, ',', ' ')
                )
            );
    }

    public function destroy(int $ficheId)
    {
        $fiche = FicheSortie::query()->findOrFail($ficheId);
        if (! $fiche->estGroupee()) {
            abort(404);
        }

        $numero = $fiche->numero_fiche ?: ('#' . $fiche->id);

        try {
            $result = $this->transfertGroupe->supprimerFicheGroupee($fiche);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('sorties_groupees.index')
            ->with(
                'success',
                sprintf(
                    'Fiche %s supprimée (%d étape(s), %d mouvement(s) de stock annulé(s)).',
                    $numero,
                    $result['etapes'],
                    $result['entrees_supprimees']
                )
            );
    }

    public function cloturerUsine(Request $request, int $ficheId)
    {
        $fiche = FicheSortie::query()->findOrFail($ficheId);
        if (! $fiche->estGroupee()) {
            abort(404);
        }

        $validated = $request->validate([
            'poids' => ['required', 'numeric', 'min:0.01'],
            'usine' => ['required', 'string', 'max:255'],
            'date_etape' => ['required', 'date'],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ]);

        // Clôture figée : agent + pont + parc de la localisation actuelle.
        if ($fiche->localisation_type !== FicheSortie::LOCALISATION_PARC || ! $fiche->localisation_stock_id) {
            return back()
                ->withErrors(['error' => 'La marchandise doit être localisée sur un parc pour clôturer vers une usine.'])
                ->withInput();
        }

        $stockSource = Stock::query()->findOrFail((int) $fiche->localisation_stock_id);

        try {
            $this->transfertGroupe->cloturerVersUsine($fiche, [
                'id_agent' => (int) $fiche->id_agent,
                'nom_agent' => $fiche->nom_agent,
                'numero_agent' => $fiche->numero_agent,
                'usine' => $validated['usine'],
                'date_etape' => $validated['date_etape'],
                'commentaire' => $validated['commentaire'] ?? null,
                'poids' => (float) $validated['poids'],
            ], $stockSource);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('fiches_sortie.non_dechargees')
            ->with(
                'success',
                sprintf(
                    'Fiche groupée clôturée vers %s (%s kg déduits de %s). Elle apparaît maintenant dans les fiches non déchargées.',
                    $validated['usine'],
                    number_format((float) $validated['poids'], 0, ',', ' '),
                    $stockSource->nom_parc ?? 'parc'
                )
            );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseAgentDisplay(string $display): array
    {
        $nomAgent = '';
        $numeroAgent = '';
        if (preg_match('/^(.+)\s+\(([^)]+)\)$/', $display, $matches)) {
            $nomAgent = trim($matches[1]);
            $numeroAgent = trim($matches[2]);
        }

        return [$nomAgent, $numeroAgent];
    }
}
