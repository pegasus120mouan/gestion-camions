<?php

namespace App\Http\Controllers;

use App\Models\EntreeStock;
use App\Models\Parc;
use App\Models\PontEtat;
use App\Models\Produit;
use App\Models\Stock;
use App\Models\TransfertPont;
use App\Services\TransfertGroupeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class TransfertPontController extends Controller
{
    public function __construct(
        private TransfertGroupeService $transfertGroupe,
    ) {}

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $dateDebut = trim((string) $request->query('date_debut', ''));
        $dateFin = trim((string) $request->query('date_fin', ''));

        $transferts = TransfertPont::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('matricule_vehicule', 'like', '%' . $q . '%')
                        ->orWhere('nom_produit', 'like', '%' . $q . '%')
                        ->orWhere('nom_pont_source', 'like', '%' . $q . '%')
                        ->orWhere('nom_pont_destination', 'like', '%' . $q . '%')
                        ->orWhere('nom_parc_source', 'like', '%' . $q . '%')
                        ->orWhere('nom_parc_destination', 'like', '%' . $q . '%');
                });
            })
            ->when($dateDebut !== '', fn ($query) => $query->whereDate('date_transfert', '>=', $dateDebut))
            ->when($dateFin !== '', fn ($query) => $query->whereDate('date_transfert', '<=', $dateFin))
            ->orderByDesc('date_transfert')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('transferts_ponts.index', [
            'transferts' => $transferts,
            'vehicules' => $this->fetchVehicules($request),
            'ponts' => $this->fetchPonts(),
            'produits' => Produit::query()->orderBy('nom')->get(),
            'parcsParPont' => Parc::query()
                ->where('statut', 'actif')
                ->orderBy('nom')
                ->get(['id', 'nom', 'id_pont'])
                ->groupBy(fn (Parc $parc) => (string) $parc->id_pont)
                ->map(fn ($parcs) => $parcs->map(fn (Parc $parc) => [
                    'id' => (int) $parc->id,
                    'nom' => (string) $parc->nom,
                ])->values()->all())
                ->all(),
            'search' => $q,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date_transfert' => ['required', 'date'],
            'vehicule_id' => ['nullable', 'integer'],
            'matricule_vehicule' => ['required', 'string', 'max:50'],
            'produit_id' => ['required', 'integer', 'exists:produits,id'],
            'id_pont_source' => ['required', 'integer'],
            'nom_pont_source' => ['nullable', 'string', 'max:255'],
            'code_pont_source' => ['nullable', 'string', 'max:100'],
            'parc_source_id' => ['nullable', 'integer', 'exists:parcs,id'],
            'stock_source_id' => ['nullable', 'integer', 'exists:stocks,id'],
            'poids_depart' => ['required', 'numeric', 'min:0.01'],
            'id_pont_destination' => ['required', 'integer', 'different:id_pont_source'],
            'nom_pont_destination' => ['nullable', 'string', 'max:255'],
            'code_pont_destination' => ['nullable', 'string', 'max:100'],
            'stock_destination_id' => ['required', 'integer', 'exists:stocks,id'],
            'poids_arrivee' => ['required', 'numeric', 'min:0.01'],
            'commentaire' => ['nullable', 'string', 'max:500'],
        ], [
            'id_pont_destination.different' => 'Le pont de destination doit être différent du pont de provenance.',
        ]);

        $produit = Produit::query()->findOrFail((int) $validated['produit_id']);
        $stockDestination = Stock::query()->findOrFail((int) $validated['stock_destination_id']);
        $idPontSource = (int) $validated['id_pont_source'];

        $pontSourceGerable = (bool) PontEtat::query()
            ->where('id_pont', $idPontSource)
            ->value('gerable');
        $parcsSourceActifs = Parc::query()
            ->where('id_pont', $idPontSource)
            ->where('statut', 'actif')
            ->exists();

        $parcSource = null;
        if (! empty($validated['parc_source_id'])) {
            $parcSource = Parc::query()->find((int) $validated['parc_source_id']);
            if (! $parcSource
                || (int) $parcSource->id_pont !== $idPontSource
                || $parcSource->statut !== 'actif'
            ) {
                return back()->withErrors(['parc_source_id' => 'Parc de provenance invalide pour ce pont.'])->withInput();
            }
        } elseif ($pontSourceGerable && $parcsSourceActifs) {
            return back()->withErrors([
                'parc_source_id' => 'Parc source obligatoire pour un pont gérable.',
            ])->withInput();
        }

        // Stock source ouvert : on débite. Sinon (pont non gérable / pas de parc / pas de stock) ce n'est pas bloquant.
        $stockSource = null;
        if ($parcSource) {
            if (! empty($validated['stock_source_id'])) {
                $stockSource = Stock::query()->find((int) $validated['stock_source_id']);
            }
            if (! $stockSource) {
                $stockSource = $this->transfertGroupe->resoudreStockParc((int) $parcSource->id, (int) $produit->id);
            }
        }

        if ((int) $stockDestination->id_pont !== (int) $validated['id_pont_destination']
            || (int) $stockDestination->produit_id !== (int) $produit->id
            || ! $stockDestination->accepteEntrees()
        ) {
            return back()->withErrors(['stock_destination_id' => 'Stock de destination invalide ou fermé pour ce pont / produit.'])->withInput();
        }

        if ($stockSource && (int) $stockSource->id === (int) $stockDestination->id) {
            return back()->withErrors(['stock_destination_id' => 'Le parc destination doit être différent du parc source.'])->withInput();
        }

        $poidsDepart = (float) $validated['poids_depart'];
        $poidsArrivee = (float) $validated['poids_arrivee'];
        $debite = false;
        $nomPontSource = $validated['nom_pont_source']
            ?? $parcSource?->nom_pont
            ?? 'pont provenance';
        $codePontSource = $validated['code_pont_source'] ?? $parcSource?->code_pont;

        if ($stockSource && $stockSource->isOuvert()) {
            $disponible = $this->transfertGroupe->calculerStockActuelChaine($stockSource);
            if ($poidsDepart > $disponible + 0.0001) {
                return back()->withErrors([
                    'poids_depart' => 'Stock insuffisant sur le parc de provenance (disponible : '
                        . number_format($disponible, 0, ',', ' ') . ' kg).',
                ])->withInput();
            }
            $debite = true;
        }

        try {
            DB::transaction(function () use (
                $validated,
                $produit,
                $parcSource,
                $stockSource,
                $stockDestination,
                $poidsDepart,
                $poidsArrivee,
                $debite,
                $nomPontSource,
                $codePontSource
            ) {
                if ($debite && $stockSource) {
                    $this->transfertGroupe->debiterStockSource(
                        $stockSource,
                        $poidsDepart,
                        sprintf(
                            'Débit transfert pont %s → %s (%s)',
                            $nomPontSource,
                            $validated['nom_pont_destination'] ?? $stockDestination->nom_pont,
                            strtoupper(trim($validated['matricule_vehicule']))
                        ),
                        $validated['date_transfert']
                    );
                }

                $entree = EntreeStock::create([
                    'stock_id' => $stockDestination->id,
                    'quantite' => $poidsArrivee,
                    'prix_unitaire' => $stockDestination->prix_unitaire,
                    'montant_total' => $stockDestination->prix_unitaire !== null
                        ? round($poidsArrivee * (float) $stockDestination->prix_unitaire, 2)
                        : null,
                    'date_entree' => $validated['date_transfert'],
                    'commentaire' => sprintf(
                        'Crédit transfert pont %s → %s (%s)',
                        $nomPontSource,
                        $validated['nom_pont_destination'] ?? $stockDestination->nom_pont,
                        strtoupper(trim($validated['matricule_vehicule']))
                    ),
                ]);

                TransfertPont::create([
                    'date_transfert' => $validated['date_transfert'],
                    'vehicule_id' => $validated['vehicule_id'] ?? null,
                    'matricule_vehicule' => strtoupper(trim($validated['matricule_vehicule'])),
                    'produit_id' => $produit->id,
                    'nom_produit' => $produit->nom,
                    'id_pont_source' => (int) $validated['id_pont_source'],
                    'nom_pont_source' => $nomPontSource,
                    'code_pont_source' => $codePontSource,
                    'parc_source_id' => $parcSource?->id,
                    'stock_source_id' => $stockSource?->id,
                    'nom_parc_source' => $parcSource?->nom,
                    'poids_depart' => $poidsDepart,
                    'id_pont_destination' => (int) $validated['id_pont_destination'],
                    'nom_pont_destination' => $validated['nom_pont_destination'] ?? $stockDestination->nom_pont,
                    'code_pont_destination' => $validated['code_pont_destination'] ?? $stockDestination->code_pont,
                    'parc_destination_id' => $stockDestination->parc_id,
                    'stock_destination_id' => $stockDestination->id,
                    'nom_parc_destination' => $stockDestination->nom_parc,
                    'poids_arrivee' => $poidsArrivee,
                    'entree_stock_id' => $entree->id,
                    'commentaire' => $validated['commentaire'] ?? null,
                    'created_by' => Auth::id(),
                ]);
            });
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }

        $message = $debite
            ? sprintf(
                'Transfert enregistré : %s kg débités de %s / %s, %s kg crédités sur %s / %s.',
                number_format($poidsDepart, 0, ',', ' '),
                $nomPontSource,
                $parcSource?->nom ?? 'parc',
                number_format($poidsArrivee, 0, ',', ' '),
                $stockDestination->nom_pont ?? 'pont destination',
                $stockDestination->nom_parc ?? 'parc'
            )
            : sprintf(
                'Transfert enregistré : %s kg crédités sur %s / %s (provenance %s — pas de débit).',
                number_format($poidsArrivee, 0, ',', ' '),
                $stockDestination->nom_pont ?? 'pont destination',
                $stockDestination->nom_parc ?? 'parc',
                $nomPontSource
            );

        return redirect()
            ->route('transferts_ponts.index')
            ->with('success', $message);
    }

    public function stocksPontProduit(Request $request)
    {
        $validated = $request->validate([
            'id_pont' => ['required', 'integer'],
            'produit_id' => ['required', 'integer', 'exists:produits,id'],
        ]);

        return response()->json([
            'stocks' => $this->transfertGroupe->stocksOuvertsPourPontProduit(
                (int) $validated['id_pont'],
                (int) $validated['produit_id']
            ),
        ]);
    }

    /**
     * @return list<array{id: int|string, label: string, nom: string, code: string, gerable: bool}>
     */
    private function fetchPonts(): array
    {
        $url = (string) config('services.external_auth.mes_ponts_url');
        if ($url === '') {
            $url = 'https://api.objetombrepegasus.online/api/camions/mes_ponts.php';
        }

        try {
            $response = Http::acceptJson()->withoutVerifying()->timeout(10)->get($url);
            if (! $response->successful()) {
                return [];
            }

            $gerableParPont = PontEtat::query()
                ->pluck('gerable', 'id_pont')
                ->map(fn ($v) => (bool) $v)
                ->all();

            $ponts = [];
            foreach ($response->json('ponts') ?? [] as $pont) {
                $id = $pont['id_pont'] ?? $pont['id'] ?? null;
                if ($id === null || $id === '') {
                    continue;
                }
                $nom = (string) ($pont['nom_pont'] ?? $pont['nom'] ?? 'Pont');
                $code = (string) ($pont['code_pont'] ?? $pont['code'] ?? '');
                $ponts[] = [
                    'id' => $id,
                    'nom' => $nom,
                    'code' => $code,
                    'label' => $code !== '' ? $nom . ' (' . $code . ')' : $nom,
                    'gerable' => (bool) ($gerableParPont[(int) $id] ?? $gerableParPont[(string) $id] ?? false),
                ];
            }

            usort($ponts, fn ($a, $b) => strcmp($a['label'], $b['label']));

            return $ponts;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return list<array{vehicule_id: int, matricule: string}>
     */
    private function fetchVehicules(Request $request): array
    {
        $url = (string) config('services.external_auth.mes_camions_url');
        if ($url === '') {
            return [];
        }

        try {
            $http = Http::acceptJson()->withoutVerifying()->timeout(10);
            $phpsessid = (string) $request->session()->get('external_auth.phpsessid', '');
            if ($phpsessid !== '') {
                $http = $http->withHeaders(['Cookie' => 'PHPSESSID=' . $phpsessid]);
            }

            $response = $http->get($url);
            if (! $response->successful()) {
                return [];
            }

            $vehicules = [];
            foreach ($response->json('vehicules') ?? [] as $v) {
                $id = (int) ($v['vehicules_id'] ?? $v['vehicule_id'] ?? $v['id'] ?? 0);
                $matricule = trim((string) ($v['matricule'] ?? $v['matricule_vehicule'] ?? ''));
                if ($matricule === '') {
                    continue;
                }
                $vehicules[] = [
                    'vehicule_id' => $id,
                    'matricule' => strtoupper($matricule),
                ];
            }

            usort($vehicules, fn ($a, $b) => strcmp($a['matricule'], $b['matricule']));

            return $vehicules;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
