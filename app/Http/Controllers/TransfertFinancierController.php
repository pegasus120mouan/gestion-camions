<?php

namespace App\Http\Controllers;

use App\Models\BordereauTransfert;
use App\Models\Client;
use App\Models\PaiementBordereauTransfert;
use App\Models\Transfert;
use App\Models\Usine;
use App\Services\BordereauTransfertService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TransfertFinancierController extends Controller
{
    public function __construct(
        private readonly BordereauTransfertService $bordereauService
    ) {
    }

    public function index(Request $request)
    {
        $tab = $request->query('tab', 'usines');
        if (!in_array($tab, ['usines', 'particuliers'], true)) {
            $tab = 'usines';
        }

        $search = trim((string) $request->query('search', ''));
        $aggregates = $this->aggregatesByClient($tab === 'usines' ? 'usine' : 'particulier');

        $rows = $tab === 'usines'
            ? $this->buildUsinesRows($aggregates, $search)
            : $this->buildParticuliersRows($aggregates, $search);

        return view('transferts.financier.index', [
            'tab' => $tab,
            'search' => $search,
            'rows' => $rows,
        ]);
    }

    public function show(Request $request, string $type, string $id)
    {
        if (!in_array($type, ['usine', 'particulier'], true)) {
            abort(404);
        }

        [$clientName, $code] = $this->resolveClientLabel($type, $id);
        if ($clientName === null) {
            abort(404, 'Client introuvable.');
        }

        $transferts = Transfert::query()
            ->where('client_type', $type)
            ->where('client_id', $id)
            ->orderByDesc('date_chargement')
            ->orderByDesc('id')
            ->get();

        $bordereaux = BordereauTransfert::query()
            ->where('client_type', $type)
            ->where('client_id', $id)
            ->orderByDesc('date_generation')
            ->orderByDesc('id')
            ->get();

        $montantDu = (float) $bordereaux->sum('montant_total');
        $montantPaye = (float) $bordereaux->sum('montant_paye');

        $historiquePaiements = PaiementBordereauTransfert::query()
            ->with('bordereau')
            ->where('client_type', $type)
            ->where('client_id', $id)
            ->orderByDesc('date_paiement')
            ->orderByDesc('id')
            ->get();

        return view('transferts.financier.show', [
            'type' => $type,
            'clientId' => $id,
            'clientName' => $clientName,
            'code' => $code,
            'transferts' => $transferts,
            'bordereaux' => $bordereaux,
            'historiquePaiements' => $historiquePaiements,
            'montantDu' => $montantDu,
            'montantPaye' => $montantPaye,
            'resteAPayer' => max(0, $montantDu - $montantPaye),
            'exempleNumeroBordereau' => $this->bordereauService->genererNumero($type, $id),
        ]);
    }

    public function transfertsEligibles(Request $request, string $type, string $id)
    {
        if (!in_array($type, ['usine', 'particulier'], true)) {
            abort(404);
        }

        $validated = $request->validate([
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
        ]);

        $lignes = $this->bordereauService
            ->lignesEligibles($type, $id, $validated['date_debut'], $validated['date_fin'])
            ->map(function (Transfert $transfert) {
                $poids = $transfert->poids_arrivee ?? $transfert->poids_depart;

                return [
                    'id' => $transfert->id,
                    'date_chargement' => $transfert->date_chargement?->format('d/m/Y'),
                    'matricule_vehicule' => $transfert->matricule_vehicule,
                    'nom_produit' => $transfert->nom_produit,
                    'lieu_depart' => $transfert->lieu_depart,
                    'lieu_destination' => $transfert->lieu_destination,
                    'poids' => $poids !== null ? (float) $poids : 0,
                    'prix_unitaire' => $transfert->prix_unitaire !== null ? (float) $transfert->prix_unitaire : null,
                    'montant' => (float) $transfert->montant,
                ];
            })
            ->values();

        return response()->json(['transferts' => $lignes]);
    }

    public function storeBordereau(Request $request, string $type, string $id)
    {
        if (!in_array($type, ['usine', 'particulier'], true)) {
            abort(404);
        }

        [$clientName, $code] = $this->resolveClientLabel($type, $id);
        if ($clientName === null) {
            abort(404, 'Client introuvable.');
        }

        $validated = $request->validate([
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'transfert_ids' => ['required', 'array', 'min:1'],
            'transfert_ids.*' => ['integer', 'exists:transferts,id'],
        ], [
            'transfert_ids.required' => 'Sélectionnez au moins un transfert.',
        ]);

        $eligibles = $this->bordereauService
            ->lignesEligibles($type, $id, $validated['date_debut'], $validated['date_fin'])
            ->whereIn('id', $validated['transfert_ids'])
            ->values();

        if ($eligibles->isEmpty()) {
            return redirect()
                ->route('transferts.financier.show', ['type' => $type, 'id' => $id])
                ->withErrors(['bordereau' => 'Aucun transfert éligible sélectionné.']);
        }

        $lignesData = $this->bordereauService->construireLignesData($eligibles);

        $bordereau = BordereauTransfert::create([
            'client_type' => $type,
            'client_id' => $id,
            'client_nom' => $clientName,
            'client_code' => $code,
            'numero' => $this->bordereauService->genererNumero($type, $id),
            'date_generation' => now()->toDateString(),
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'],
            'montant_total' => collect($lignesData)->sum('montant'),
            'montant_paye' => 0,
            'poids_total' => collect($lignesData)->sum('poids'),
            'transferts_data' => $lignesData,
        ]);

        $this->bordereauService->assignerLignesAuBordereau($bordereau, $lignesData);

        return redirect()
            ->route('transferts.financier.show', ['type' => $type, 'id' => $id])
            ->with('success', 'Bordereau ' . $bordereau->numero . ' généré avec succès.');
    }

    public function showBordereau(string $type, string $id, BordereauTransfert $bordereau)
    {
        $this->assertBordereauBelongsToClient($bordereau, $type, $id);

        return view('transferts.financier.bordereau_show', [
            'type' => $type,
            'clientId' => $id,
            'bordereau' => $bordereau,
        ]);
    }

    public function exportBordereauPdf(string $type, string $id, BordereauTransfert $bordereau)
    {
        $this->assertBordereauBelongsToClient($bordereau, $type, $id);

        $logoPath = public_path('img/logo/logo.png');
        if (!file_exists($logoPath)) {
            $logoPath = null;
        }

        $pdf = Pdf::loadView('transferts.financier.bordereau_pdf', [
            'bordereau' => $bordereau,
            'lignes' => $bordereau->transferts_data ?? [],
            'clientTypeLabel' => $type === 'usine' ? 'Usine' : 'Particulier',
            'logoPath' => $logoPath,
            'dateCreation' => ($bordereau->created_at ?? $bordereau->date_generation ?? now())->format('d/m/Y \à H:i'),
        ]);

        $pdf->setPaper('A4', 'portrait');

        $filename = 'bordereau_' . preg_replace('/[^a-zA-Z0-9_-]+/', '_', $bordereau->numero) . '.pdf';

        return $pdf->stream($filename);
    }

    public function destroyBordereau(string $type, string $id, BordereauTransfert $bordereau)
    {
        $this->assertBordereauBelongsToClient($bordereau, $type, $id);

        if ((float) ($bordereau->montant_paye ?? 0) > 0) {
            return redirect()
                ->route('transferts.financier.show', ['type' => $type, 'id' => $id])
                ->withErrors(['bordereau' => 'Impossible de supprimer un bordereau déjà payé.']);
        }

        $this->bordereauService->libererLignes($bordereau);
        $bordereau->delete();

        return redirect()
            ->route('transferts.financier.show', ['type' => $type, 'id' => $id])
            ->with('success', 'Bordereau supprimé.');
    }

    public function storePaiementBordereau(Request $request, string $type, string $id, BordereauTransfert $bordereau)
    {
        $this->assertBordereauBelongsToClient($bordereau, $type, $id);

        $reste = $bordereau->reste_a_payer;
        $request->merge([
            'montant' => preg_replace('/\s+/u', '', (string) $request->input('montant', '')),
        ]);
        $validated = $request->validate([
            'montant' => ['required', 'numeric', 'min:1', 'max:' . max(1, $reste)],
        ], [
            'montant.required' => 'Le montant est obligatoire.',
            'montant.max' => 'Le montant ne peut pas dépasser le reste à payer.',
        ]);

        $nouveauPaye = (float) $bordereau->montant_paye + (float) $validated['montant'];

        PaiementBordereauTransfert::create([
            'bordereau_transfert_id' => $bordereau->id,
            'client_type' => $type,
            'client_id' => $id,
            'montant' => (float) $validated['montant'],
            'date_paiement' => now()->toDateString(),
            'observation' => 'Paiement bordereau ' . $bordereau->numero,
            'user_id' => Auth::id(),
        ]);

        $bordereau->update([
            'montant_paye' => $nouveauPaye,
        ]);

        if ($nouveauPaye + 0.0001 >= (float) $bordereau->montant_total) {
            Transfert::query()
                ->where('bordereau_transfert_id', $bordereau->id)
                ->update(['paiement' => Transfert::PAIEMENT_PAYE]);
        }

        return redirect()
            ->route('transferts.financier.show', ['type' => $type, 'id' => $id])
            ->with('success', 'Paiement enregistré sur le bordereau ' . $bordereau->numero . '.');
    }

    private function assertBordereauBelongsToClient(BordereauTransfert $bordereau, string $type, string $id): void
    {
        if ($bordereau->client_type !== $type || (string) $bordereau->client_id !== (string) $id) {
            abort(404);
        }
    }

    /**
     * @return Collection<string, object{nb_transferts: int, montant_du: float, montant_paye: float, client: string}>
     */
    private function aggregatesByClient(string $clientType): Collection
    {
        $fromBordereaux = BordereauTransfert::query()
            ->where('client_type', $clientType)
            ->select([
                'client_id',
                DB::raw('MAX(client_nom) as client'),
                DB::raw('SUM(montant_total) as montant_du'),
                DB::raw('SUM(COALESCE(montant_paye, 0)) as montant_paye'),
            ])
            ->groupBy('client_id')
            ->get()
            ->keyBy(fn ($row) => (string) $row->client_id);

        $nbTransferts = Transfert::query()
            ->where('client_type', $clientType)
            ->select([
                'client_id',
                DB::raw('MAX(client) as client'),
                DB::raw('COUNT(*) as nb_transferts'),
            ])
            ->groupBy('client_id')
            ->get()
            ->keyBy(fn ($row) => (string) $row->client_id);

        $clientIds = $fromBordereaux->keys()->merge($nbTransferts->keys())->unique();

        return $clientIds->mapWithKeys(function (string $clientId) use ($fromBordereaux, $nbTransferts) {
            $bord = $fromBordereaux->get($clientId);
            $tr = $nbTransferts->get($clientId);

            return [
                $clientId => (object) [
                    'client_id' => $clientId,
                    'client' => (string) ($bord->client ?? $tr->client ?? ''),
                    'nb_transferts' => (int) ($tr->nb_transferts ?? 0),
                    'montant_du' => (float) ($bord->montant_du ?? 0),
                    'montant_paye' => (float) ($bord->montant_paye ?? 0),
                ],
            ];
        });
    }

    /**
     * @param  Collection<string, object>  $aggregates
     * @return Collection<int, array{client_type: string, client_id: string, client: string, code: ?string, nb_transferts: int, montant_du: float, montant_paye: float, reste_a_payer: float}>
     */
    private function buildUsinesRows(Collection $aggregates, string $search): Collection
    {
        $usines = collect($this->fetchUsinesForOptions());

        // Inclure aussi les usines présentes uniquement dans les transferts
        foreach ($aggregates as $clientId => $agg) {
            if (!$usines->contains(fn ($u) => (string) $u['id'] === (string) $clientId)) {
                $usines->push([
                    'id' => (string) $clientId,
                    'name' => (string) ($agg->client ?: 'Usine #' . $clientId),
                ]);
            }
        }

        return $usines
            ->map(function (array $usine) use ($aggregates) {
                $id = (string) $usine['id'];
                $agg = $aggregates->get($id);
                $montantDu = (float) ($agg->montant_du ?? 0);
                $montantPaye = (float) ($agg->montant_paye ?? 0);

                return [
                    'client_type' => 'usine',
                    'client_id' => $id,
                    'client' => (string) $usine['name'],
                    'code' => null,
                    'nb_transferts' => (int) ($agg->nb_transferts ?? 0),
                    'montant_du' => $montantDu,
                    'montant_paye' => $montantPaye,
                    'reste_a_payer' => $montantDu - $montantPaye,
                ];
            })
            ->when($search !== '', function (Collection $collection) use ($search) {
                $needle = $this->normalizeSearch($search);

                return $collection->filter(function (array $item) use ($needle) {
                    $haystack = $this->normalizeSearch(
                        ($item['client'] ?? '') . ' ' . ($item['client_id'] ?? '') . ' ' . ($item['code'] ?? '')
                    );

                    return $needle === '' || str_contains($haystack, $needle);
                });
            })
            ->sortBy(fn (array $item) => mb_strtoupper($item['client'], 'UTF-8'))
            ->values();
    }

    /**
     * @param  Collection<string, object>  $aggregates
     * @return Collection<int, array{client_type: string, client_id: string, client: string, code: ?string, nb_transferts: int, montant_du: float, montant_paye: float, reste_a_payer: float}>
     */
    private function buildParticuliersRows(Collection $aggregates, string $search): Collection
    {
        $particuliers = Client::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('nom', 'like', '%' . $search . '%')
                        ->orWhere('prenoms', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%')
                        ->orWhere('contact', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('nom')
            ->orderBy('prenoms')
            ->get();

        $rows = $particuliers->map(function (Client $client) use ($aggregates) {
            $id = (string) $client->id;
            $agg = $aggregates->get($id);
            $montantDu = (float) ($agg->montant_du ?? 0);
            $montantPaye = (float) ($agg->montant_paye ?? 0);

            return [
                'client_type' => 'particulier',
                'client_id' => $id,
                'client' => $client->nom_complet,
                'code' => $client->code,
                'nb_transferts' => (int) ($agg->nb_transferts ?? 0),
                'montant_du' => $montantDu,
                'montant_paye' => $montantPaye,
                'reste_a_payer' => $montantDu - $montantPaye,
            ];
        });

        // Particuliers présents uniquement dans les transferts (supprimés de clients)
        foreach ($aggregates as $clientId => $agg) {
            if ($rows->contains(fn ($row) => $row['client_id'] === (string) $clientId)) {
                continue;
            }

            if ($search !== '') {
                $needle = $this->normalizeSearch($search);
                $label = $this->normalizeSearch((string) $agg->client . ' ' . $clientId);
                if ($needle !== '' && !str_contains($label, $needle)) {
                    continue;
                }
            }

            $montantDu = (float) ($agg->montant_du ?? 0);
            $montantPaye = (float) ($agg->montant_paye ?? 0);
            $rows->push([
                'client_type' => 'particulier',
                'client_id' => (string) $clientId,
                'client' => (string) ($agg->client ?: 'Particulier #' . $clientId),
                'code' => null,
                'nb_transferts' => (int) ($agg->nb_transferts ?? 0),
                'montant_du' => $montantDu,
                'montant_paye' => $montantPaye,
                'reste_a_payer' => $montantDu - $montantPaye,
            ]);
        }

        return $rows
            ->when($search !== '', function (Collection $collection) use ($search) {
                $needle = $this->normalizeSearch($search);

                return $collection->filter(function (array $item) use ($needle) {
                    $haystack = $this->normalizeSearch(
                        ($item['client'] ?? '') . ' ' . ($item['client_id'] ?? '') . ' ' . ($item['code'] ?? '')
                    );

                    return $needle === '' || str_contains($haystack, $needle);
                });
            })
            ->sortBy(fn (array $item) => mb_strtoupper($item['client'], 'UTF-8'))
            ->values();
    }

    private function normalizeSearch(string $value): string
    {
        $value = mb_strtolower(trim($value), 'UTF-8');
        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($value, \Normalizer::FORM_D);
            if (is_string($normalized)) {
                $value = preg_replace('/\p{Mn}+/u', '', $normalized) ?? $value;
            }
        }

        return $value;
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveClientLabel(string $type, string $id): array
    {
        if ($type === 'particulier') {
            $client = Client::query()->find($id);
            if ($client) {
                return [$client->nom_complet, $client->code];
            }

            $fromTransfert = Transfert::query()
                ->where('client_type', 'particulier')
                ->where('client_id', $id)
                ->value('client');

            return [$fromTransfert ?: null, null];
        }

        $usine = collect($this->fetchUsinesForOptions())
            ->first(fn ($u) => (string) ($u['id'] ?? '') === (string) $id);

        if ($usine) {
            return [(string) $usine['name'], null];
        }

        $local = Usine::query()->where('id_usine', $id)->first();
        if ($local) {
            return [$local->nom_usine, null];
        }

        $fromTransfert = Transfert::query()
            ->where('client_type', 'usine')
            ->where('client_id', $id)
            ->value('client');

        return [$fromTransfert ?: null, null];
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function fetchUsinesForOptions(): array
    {
        $mesUsinesUrl = (string) config('services.external_auth.mes_usines_url');
        $timeout = (int) config('services.external_auth.timeout', 10);
        $usinesApi = [];

        if ($mesUsinesUrl !== '') {
            try {
                $queryParams = ['page' => 1];
                $response = Http::acceptJson()
                    ->withoutVerifying()
                    ->timeout($timeout)
                    ->get($mesUsinesUrl, $queryParams);

                if ($response->successful()) {
                    $pageUsines = $response->json('usines');
                    if (is_array($pageUsines)) {
                        $usinesApi = $pageUsines;
                    }

                    $lastPage = (int) ($response->json('pagination.last_page') ?? 1);
                    for ($apiPage = 2; $apiPage <= $lastPage; $apiPage++) {
                        $queryParams['page'] = $apiPage;
                        $pageResponse = Http::acceptJson()
                            ->withoutVerifying()
                            ->timeout($timeout)
                            ->get($mesUsinesUrl, $queryParams);

                        if (!$pageResponse->successful()) {
                            break;
                        }

                        $pageUsines = $pageResponse->json('usines');
                        if (is_array($pageUsines)) {
                            $usinesApi = array_merge($usinesApi, $pageUsines);
                        }
                    }
                }
            } catch (\Throwable $e) {
                // fallback local
            }
        }

        $idsApi = collect($usinesApi)
            ->map(fn ($u) => (string) ($u['id_usine'] ?? ''))
            ->filter()
            ->all();

        $nomsApi = collect($usinesApi)
            ->map(fn ($u) => mb_strtolower(trim((string) ($u['nom_usine'] ?? '')), 'UTF-8'))
            ->filter()
            ->all();

        $locales = Usine::query()
            ->orderBy('nom_usine')
            ->get()
            ->filter(function (Usine $usine) use ($idsApi, $nomsApi) {
                $id = (string) $usine->id_usine;
                $key = mb_strtolower(trim((string) $usine->nom_usine), 'UTF-8');

                if ($id !== '' && in_array($id, $idsApi, true)) {
                    return false;
                }

                return $key !== '' && !in_array($key, $nomsApi, true);
            })
            ->map(fn (Usine $usine) => [
                'id' => (string) $usine->id_usine,
                'name' => $usine->nom_usine,
            ]);

        $api = collect($usinesApi)
            ->filter(fn ($u) => !empty($u['id_usine']) && !empty($u['nom_usine']))
            ->map(fn ($u) => [
                'id' => (string) $u['id_usine'],
                'name' => (string) $u['nom_usine'],
            ]);

        return $locales
            ->concat($api)
            ->unique('id')
            ->sortBy(fn ($u) => mb_strtoupper($u['name'], 'UTF-8'))
            ->values()
            ->all();
    }
}
