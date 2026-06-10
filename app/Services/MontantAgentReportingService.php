<?php

namespace App\Services;

use App\Models\FicheSortie;
use App\Models\Produit;
use App\Models\Ticket;
use App\Models\Usine;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class MontantAgentReportingService
{
    public function __construct(
        private MontantAgentFicheService $montantAgentFiche
    ) {}

    /**
     * @return array{
     *   produit_id: int|null,
     *   usine: string|null,
     *   date_debut: string|null,
     *   date_fin: string|null,
     *   id_agent: int|null
     * }
     */
    public function filtresDepuisRequest(Request $request): array
    {
        $produitId = $request->filled('produit_id') ? (int) $request->input('produit_id') : null;

        return [
            'produit_id' => $produitId > 0 ? $produitId : null,
            'usine' => $request->filled('usine') ? trim((string) $request->input('usine')) : null,
            'date_debut' => $request->filled('date_debut') ? (string) $request->input('date_debut') : null,
            'date_fin' => $request->filled('date_fin') ? (string) $request->input('date_fin') : null,
            'id_agent' => $request->filled('id_agent') ? (int) $request->input('id_agent') : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $filtres
     */
    public function queryFichesDechargees(array $filtres = []): Builder
    {
        $query = FicheSortie::query()
            ->whereNotNull('date_dechargement')
            ->whereNotNull('id_agent');

        if (!empty($filtres['id_agent'])) {
            $query->where('id_agent', (int) $filtres['id_agent']);
        }

        if (!empty($filtres['produit_id'])) {
            $query->where('produit_id', (int) $filtres['produit_id']);
        }

        if (!empty($filtres['usine'])) {
            $query->where('usine', $filtres['usine']);
        }

        if (!empty($filtres['date_debut'])) {
            $query->whereDate('date_dechargement', '>=', $filtres['date_debut']);
        }

        if (!empty($filtres['date_fin'])) {
            $query->whereDate('date_dechargement', '<=', $filtres['date_fin']);
        }

        return $query;
    }

    public function montantLigneFiche(FicheSortie $fiche): int
    {
        if ($fiche->montant_agent !== null) {
            return (int) round((float) $fiche->montant_agent);
        }

        $pu = $this->montantAgentFiche->prixUnitairePourFiche($fiche);
        $poids = (float) $fiche->poids_pont;

        if ($poids <= 0 && $fiche->id_ticket) {
            $ticket = Ticket::where('id_ticket', $fiche->id_ticket)->first();
            $poids = $ticket ? (float) ($ticket->poids ?? 0) : 0;
        }

        return $pu !== null && $poids > 0
            ? (int) round($pu * $poids)
            : 0;
    }

    /**
     * @param  array<string, mixed>  $filtres
     */
    public function calculerMontantDuAgent(int $idAgent, array $filtres = []): float
    {
        $filtres['id_agent'] = $idAgent;
        $fiches = $this->queryFichesDechargees($filtres)->get();
        $total = 0.0;

        foreach ($fiches as $fiche) {
            $total += $this->montantLigneFiche($fiche);
        }

        return $total;
    }

    /**
     * @param  array<string, mixed>  $filtres
     * @return list<array{fiche: FicheSortie, montant: int, prix_unitaire: float|null}>
     */
    public function fichesAvecMontant(array $filtres = []): array
    {
        $fiches = $this->queryFichesDechargees($filtres)
            ->orderBy('nom_produit')
            ->orderBy('usine')
            ->orderBy('date_chargement', 'desc')
            ->get();

        $usinesById = $this->buildUsinesById();

        $result = [];
        foreach ($fiches as $fiche) {
            if (is_numeric($fiche->usine) && isset($usinesById[(string) $fiche->usine])) {
                $fiche->usine = $usinesById[(string) $fiche->usine];
            }
            $poids = (float) $fiche->poids_pont;
            if ($poids <= 0 && $fiche->id_ticket) {
                $ticket = Ticket::where('id_ticket', $fiche->id_ticket)->first();
                $poids = $ticket ? (float) ($ticket->poids ?? 0) : 0;
            }
            $result[] = [
                'fiche' => $fiche,
                'montant' => $this->montantLigneFiche($fiche),
                'prix_unitaire' => $this->montantAgentFiche->prixUnitairePourFiche($fiche),
                'poids_effectif' => $poids,
            ];
        }

        return $result;
    }

    private function buildUsinesById(): array
    {
        $index = [];
        foreach (Usine::all() as $ul) {
            $index[(string) $ul->id_usine] = $ul->nom_usine;
        }
        try {
            $url = (string) config('services.external_auth.mes_usines_url');
            $timeout = (int) config('services.external_auth.timeout', 10);
            $resp = Http::acceptJson()->withoutVerifying()->timeout($timeout)->get($url);
            if ($resp->successful()) {
                foreach ($resp->json('usines') ?? [] as $u) {
                    $key = (string) ($u['id_usine'] ?? '');
                    if ($key !== '' && !isset($index[$key])) {
                        $index[$key] = $u['nom_usine'] ?? '';
                    }
                }
            }
        } catch (\Throwable $e) {}

        return $index;
    }

    /**
     * @param  list<array{fiche: FicheSortie, montant: int, prix_unitaire: float|null}>  $fichesAvecMontant
     * @return list<array{
     *   produit: string,
     *   produit_id: int|null,
     *   montant_total: int,
     *   poids_total: float,
     *   nb_fiches: int,
     *   usines: list<array{
     *     usine: string,
     *     montant_total: int,
     *     poids_total: float,
     *     nb_fiches: int,
     *     lignes: list<array{fiche: FicheSortie, montant: int, prix_unitaire: float|null}>
     *   }>
     * }>
     */
    public function grouperParProduitEtUsine(array $fichesAvecMontant): array
    {
        $parProduit = collect($fichesAvecMontant)->groupBy(function ($item) {
            $fiche = $item['fiche'];

            return $fiche->nom_produit ?: 'Sans produit';
        });

        $groupes = [];
        foreach ($parProduit as $nomProduit => $itemsProduit) {
            $produitId = $itemsProduit->first()['fiche']->produit_id ?? null;
            $parUsine = $itemsProduit->groupBy(fn ($item) => $item['fiche']->usine ?: 'Sans usine');
            $usines = [];

            foreach ($parUsine as $nomUsine => $lignes) {
                $lignesArr = $lignes->values()->all();
                $usines[] = [
                    'usine' => $nomUsine,
                    'montant_total' => (int) $lignes->sum('montant'),
                    'poids_total' => (float) $lignes->sum(fn ($i) => (float) ($i['poids_effectif'] ?? $i['fiche']->poids_pont ?? 0)),
                    'nb_fiches' => count($lignesArr),
                    'lignes' => $lignesArr,
                ];
            }

            usort($usines, fn ($a, $b) => strcasecmp($a['usine'], $b['usine']));

            $groupes[] = [
                'produit' => $nomProduit,
                'produit_id' => $produitId,
                'montant_total' => (int) $itemsProduit->sum('montant'),
                'poids_total' => (float) $itemsProduit->sum(fn ($i) => (float) ($i['poids_effectif'] ?? $i['fiche']->poids_pont ?? 0)),
                'nb_fiches' => $itemsProduit->count(),
                'usines' => $usines,
            ];
        }

        usort($groupes, fn ($a, $b) => strcasecmp($a['produit'], $b['produit']));

        return $groupes;
    }

    /**
     * Synthèse par produit (tous agents).
     *
     * @param  array<string, mixed>  $filtres
     * @return list<array{
     *   produit: string,
     *   produit_id: int|null,
     *   montant_total: int,
     *   poids_total: float,
     *   nb_fiches: int,
     *   nb_agents: int,
     *   usines: list<array{usine: string, montant_total: int, poids_total: float, nb_fiches: int}>
     * }>
     */
    public function syntheseParProduit(array $filtres = []): array
    {
        $fichesAvecMontant = $this->fichesAvecMontant($filtres);
        $groupes = $this->grouperParProduitEtUsine($fichesAvecMontant);

        return array_map(function ($groupe) {
            $agentsIds = collect($groupe['usines'])
                ->flatMap(fn ($u) => collect($u['lignes'])->pluck('fiche.id_agent'))
                ->unique()
                ->filter();

            $groupe['nb_agents'] = $agentsIds->count();
            $groupe['usines'] = array_map(function ($usine) {
                unset($usine['lignes']);

                return $usine;
            }, $groupe['usines']);

            return $groupe;
        }, $groupes);
    }

    /**
     * @return array{produits: \Illuminate\Database\Eloquent\Collection, usines: list<string>}
     */
    public function optionsFiltres(): array
    {
        $produits = Produit::orderBy('nom')->get();

        $usinesFiches = FicheSortie::query()
            ->whereNotNull('date_dechargement')
            ->whereNotNull('usine')
            ->where('usine', '!=', '')
            ->distinct()
            ->orderBy('usine')
            ->pluck('usine')
            ->all();

        if (Schema::hasColumn('usines', 'produit_id')) {
            $usinesLocales = Usine::query()->orderBy('nom_usine')->pluck('nom_usine')->all();
            $usines = array_values(array_unique(array_merge($usinesLocales, $usinesFiches)));
            sort($usines);
        } else {
            $usines = $usinesFiches;
        }

        return [
            'produits' => $produits,
            'usines' => $usines,
        ];
    }

    /**
     * @param  array<string, mixed>  $filtres
     */
    public function filtresPourUrl(array $filtres): array
    {
        return array_filter([
            'produit_id' => $filtres['produit_id'] ?? null,
            'usine' => $filtres['usine'] ?? null,
            'date_debut' => $filtres['date_debut'] ?? null,
            'date_fin' => $filtres['date_fin'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    public function filtresActifs(array $filtres): bool
    {
        return ($filtres['produit_id'] ?? null)
            || ($filtres['usine'] ?? null)
            || ($filtres['date_debut'] ?? null)
            || ($filtres['date_fin'] ?? null);
    }
}
