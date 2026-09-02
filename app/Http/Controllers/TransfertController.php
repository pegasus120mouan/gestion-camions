<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientSite;
use App\Models\Transfert;
use App\Models\Usine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class TransfertController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $dateDebut = trim((string) $request->query('date_debut', ''));
        $dateFin = trim((string) $request->query('date_fin', ''));

        $transferts = Transfert::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('matricule_vehicule', 'like', '%' . $q . '%')
                        ->orWhere('client', 'like', '%' . $q . '%')
                        ->orWhere('lieu_depart', 'like', '%' . $q . '%')
                        ->orWhere('lieu_destination', 'like', '%' . $q . '%');
                });
            })
            ->when($dateDebut !== '', fn ($query) => $query->whereDate('date_chargement', '>=', $dateDebut))
            ->when($dateFin !== '', fn ($query) => $query->whereDate('date_chargement', '<=', $dateFin))
            ->orderByDesc('date_chargement')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('transferts.index', [
            'transferts' => $transferts,
            'vehicules' => $this->fetchVehicules($request),
            'clientsOptions' => $this->buildClientsOptions(),
            'search' => $q,
            'dateDebut' => $dateDebut,
            'dateFin' => $dateFin,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateTransfert($request);

        Transfert::create([
            'date_chargement' => $validated['date_chargement'],
            'vehicule_id' => $validated['vehicule_id'] ?? null,
            'matricule_vehicule' => strtoupper(trim($validated['matricule_vehicule'])),
            'client' => trim($validated['client']),
            'client_type' => $validated['client_type'],
            'client_id' => (string) $validated['client_id'],
            'lieu_depart' => trim($validated['lieu_depart']),
            'lieu_destination' => trim($validated['lieu_destination']),
            'poids_depart' => $validated['poids_depart'] ?? null,
            'poids_arrivee' => $validated['poids_arrivee'] ?? null,
            'montant' => null,
            'statut' => Transfert::STATUT_NON_DECHARGE,
            'paiement' => Transfert::PAIEMENT_NON_PAYE,
            'commentaire' => null,
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('transferts.index')
            ->with('success', 'Transfert enregistré avec succès.');
    }

    public function update(Request $request, Transfert $transfert)
    {
        if ($this->isPaye($transfert)) {
            return redirect()
                ->route('transferts.index')
                ->withErrors(['paiement' => 'Ce transfert est payé : modification impossible.']);
        }

        $validated = $this->validateTransfert($request);

        $transfert->update([
            'date_chargement' => $validated['date_chargement'],
            'vehicule_id' => $validated['vehicule_id'] ?? null,
            'matricule_vehicule' => strtoupper(trim($validated['matricule_vehicule'])),
            'client' => trim($validated['client']),
            'client_type' => $validated['client_type'],
            'client_id' => (string) $validated['client_id'],
            'lieu_depart' => trim($validated['lieu_depart']),
            'lieu_destination' => trim($validated['lieu_destination']),
            'poids_depart' => $validated['poids_depart'] ?? null,
            'poids_arrivee' => $validated['poids_arrivee'] ?? null,
        ]);

        return redirect()
            ->route('transferts.index')
            ->with('success', 'Transfert mis à jour.');
    }

    public function destroy(Transfert $transfert)
    {
        if ($this->isPaye($transfert)) {
            return redirect()
                ->route('transferts.index')
                ->withErrors(['paiement' => 'Ce transfert est payé : suppression impossible.']);
        }

        $transfert->delete();

        return redirect()
            ->route('transferts.index')
            ->with('success', 'Transfert supprimé.');
    }

    public function markDecharge(Transfert $transfert)
    {
        if (($transfert->statut ?? Transfert::STATUT_NON_DECHARGE) === Transfert::STATUT_DECHARGE) {
            return redirect()
                ->route('transferts.index')
                ->with('success', 'Ce transfert est déjà déchargé.');
        }

        $transfert->update([
            'statut' => Transfert::STATUT_DECHARGE,
        ]);

        return redirect()
            ->route('transferts.index')
            ->with('success', 'Transfert marqué comme déchargé.');
    }

    public function updatePrixUnitaire(Request $request, Transfert $transfert)
    {
        if ($this->isPaye($transfert)) {
            return redirect()
                ->route('transferts.index')
                ->withErrors(['paiement' => 'Ce transfert est payé : modification du prix impossible.']);
        }

        $validated = $request->validate([
            'prix_unitaire' => ['required', 'numeric', 'min:0'],
        ], [
            'prix_unitaire.required' => 'Le prix unitaire est obligatoire.',
            'prix_unitaire.numeric' => 'Le prix unitaire doit être un nombre.',
        ]);

        $prixUnitaire = (float) $validated['prix_unitaire'];
        $poids = $transfert->poids_arrivee ?? $transfert->poids_depart;
        $montant = $poids !== null ? round($prixUnitaire * (float) $poids, 2) : null;

        $transfert->update([
            'prix_unitaire' => $prixUnitaire,
            'montant' => $montant,
        ]);

        return redirect()
            ->route('transferts.index')
            ->with('success', 'Prix unitaire enregistré avec succès.');
    }

    private function isPaye(Transfert $transfert): bool
    {
        return ($transfert->paiement ?? Transfert::PAIEMENT_NON_PAYE) === Transfert::PAIEMENT_PAYE;
    }

    private function validateTransfert(Request $request): array
    {
        $validated = $request->validate([
            'date_chargement' => ['required', 'date'],
            'vehicule_id' => ['nullable', 'integer', 'min:1'],
            'matricule_vehicule' => ['required', 'string', 'max:100'],
            'client_type' => ['required', 'in:usine,particulier'],
            'client_id' => ['required', 'string', 'max:50'],
            'client' => ['required', 'string', 'max:255'],
            'lieu_depart' => ['required', 'string', 'max:255'],
            'lieu_destination' => ['required', 'string', 'max:255'],
            'poids_depart' => ['nullable', 'numeric', 'min:0'],
            'poids_arrivee' => ['nullable', 'numeric', 'min:0'],
        ], [
            'date_chargement.required' => 'La date de chargement est obligatoire.',
            'matricule_vehicule.required' => 'Le véhicule est obligatoire.',
            'client_type.required' => 'Le type de client est obligatoire.',
            'client_id.required' => 'Le client est obligatoire.',
            'client.required' => 'Le client est obligatoire.',
            'lieu_depart.required' => 'Le lieu de départ est obligatoire.',
            'lieu_destination.required' => 'Le lieu de destination est obligatoire.',
        ]);

        $this->assertClientExists($validated['client_type'], $validated['client_id']);
        $this->assertSiteBelongsToClient($validated['client_type'], $validated['client_id'], $validated['lieu_depart'], 'lieu_depart');
        $this->assertSiteBelongsToClient($validated['client_type'], $validated['client_id'], $validated['lieu_destination'], 'lieu_destination');

        return $validated;
    }

    private function assertClientExists(string $type, string $id): void
    {
        if ($type === 'particulier') {
            if (!Client::query()->whereKey($id)->exists()) {
                throw ValidationException::withMessages([
                    'client_id' => 'Particulier introuvable.',
                ]);
            }

            return;
        }

        $found = collect($this->fetchUsinesForOptions())
            ->contains(fn ($u) => (string) ($u['id'] ?? '') === (string) $id);

        if (!$found) {
            throw ValidationException::withMessages([
                'client_id' => 'Usine introuvable.',
            ]);
        }
    }

    private function assertSiteBelongsToClient(string $type, string $id, string $siteNom, string $field = 'lieu_depart'): void
    {
        $exists = ClientSite::forOwner($type, $id)
            ->where('nom', $siteNom)
            ->exists();

        if (!$exists) {
            throw ValidationException::withMessages([
                $field => 'Les lieux doivent être des sites du client choisi.',
            ]);
        }
    }

    /**
     * @return array{usine: list<array{id: string, label: string, name: string, sites: list<array{value: string, label: string}>}>, particulier: list<array{id: string, label: string, name: string, sites: list<array{value: string, label: string}>}>}
     */
    private function buildClientsOptions(): array
    {
        $sitesByOwner = ClientSite::query()
            ->orderBy('nom')
            ->get()
            ->groupBy(fn (ClientSite $site) => $site->owner_type . ':' . $site->owner_id);

        $mapSites = function (string $type, string|int $id) use ($sitesByOwner): array {
            $key = $type . ':' . $id;

            return ($sitesByOwner->get($key) ?? collect())
                ->map(function (ClientSite $site) {
                    $label = $site->nom;
                    if ($site->adresse) {
                        $label .= ' — ' . $site->adresse;
                    }

                    return [
                        'value' => $site->nom,
                        'label' => $label,
                    ];
                })
                ->values()
                ->all();
        };

        $particuliers = Client::query()
            ->orderBy('nom')
            ->orderBy('prenoms')
            ->get()
            ->map(function (Client $client) use ($mapSites) {
                return [
                    'id' => (string) $client->id,
                    'label' => trim(($client->code ? $client->code . ' — ' : '') . $client->nom_complet),
                    'name' => $client->nom_complet,
                    'sites' => $mapSites('particulier', $client->id),
                ];
            })
            ->values()
            ->all();

        $usines = collect($this->fetchUsinesForOptions())
            ->map(function (array $usine) use ($mapSites) {
                $id = (string) $usine['id'];

                return [
                    'id' => $id,
                    'label' => $usine['name'],
                    'name' => $usine['name'],
                    'sites' => $mapSites('usine', $id),
                ];
            })
            ->values()
            ->all();

        return [
            'usine' => $usines,
            'particulier' => $particuliers,
        ];
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
                // ignore API errors, fallback to local
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
            if (!$response->successful()) {
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
