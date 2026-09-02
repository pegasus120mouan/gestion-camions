<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientSite;
use App\Models\Usine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'usines');
        if (!in_array($tab, ['usines', 'particuliers'], true)) {
            $tab = 'usines';
        }

        $search = trim((string) $request->query('search', ''));

        return view('clients.index', [
            'tab' => $tab,
            'search' => $search,
            'usines' => $tab === 'usines' ? $this->fetchUsines($search) : [],
            'usinesError' => $tab === 'usines' ? ($this->lastUsinesError ?? null) : null,
            'particuliers' => $tab === 'particuliers'
                ? Client::query()
                    ->when($search !== '', function ($query) use ($search) {
                        $query->where(function ($sub) use ($search) {
                            $sub->where('code', 'like', '%' . $search . '%')
                                ->orWhere('nom', 'like', '%' . $search . '%')
                                ->orWhere('prenoms', 'like', '%' . $search . '%')
                                ->orWhere('contact', 'like', '%' . $search . '%');
                        });
                    })
                    ->orderByDesc('id')
                    ->paginate(20)
                    ->withQueryString()
                : null,
        ]);
    }

    public function showUsine(string $id)
    {
        $usineData = $this->resolveUsineById($id);

        if ($usineData === null) {
            abort(404, 'Usine introuvable.');
        }

        return view('clients.show_usine', [
            'usine' => $usineData,
            'sites' => ClientSite::forOwner('usine', $id)->orderBy('nom')->get(),
        ]);
    }

    public function show(Client $client)
    {
        return view('clients.show', [
            'client' => $client,
            'sites' => ClientSite::forOwner('particulier', $client->id)->orderBy('nom')->get(),
        ]);
    }

    public function storeSite(Request $request)
    {
        $validated = $this->validateSite($request);

        $ownerType = $validated['owner_type'];
        $ownerId = (string) $validated['owner_id'];
        $ownerNom = null;

        if ($ownerType === 'usine') {
            $usine = $this->resolveUsineById($ownerId);
            if ($usine === null) {
                abort(404, 'Usine introuvable.');
            }
            $ownerNom = $usine['nom_usine'];
        } else {
            $client = Client::query()->findOrFail($ownerId);
            $ownerNom = $client->nom_complet;
        }

        ClientSite::create([
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'owner_nom' => $ownerNom,
            'nom' => trim($validated['nom']),
            'adresse' => isset($validated['adresse']) ? trim($validated['adresse']) : null,
        ]);

        return redirect()
            ->to($this->ownerRedirectUrl($ownerType, $ownerId))
            ->with('success', 'Site enregistré avec succès.');
    }

    public function updateSite(Request $request, ClientSite $site)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
        ], [
            'nom.required' => 'Le nom du site est obligatoire.',
        ]);

        $site->update([
            'nom' => trim($validated['nom']),
            'adresse' => isset($validated['adresse']) ? trim($validated['adresse']) : null,
        ]);

        return redirect()
            ->to($this->ownerRedirectUrl($site->owner_type, $site->owner_id))
            ->with('success', 'Site modifié avec succès.');
    }

    public function destroySite(ClientSite $site)
    {
        $redirect = $this->ownerRedirectUrl($site->owner_type, $site->owner_id);
        $site->delete();

        return redirect()
            ->to($redirect)
            ->with('success', 'Site supprimé avec succès.');
    }

    /**
     * @return array{owner_type: string, owner_id: string, nom: string, adresse?: string}
     */
    private function validateSite(Request $request): array
    {
        return $request->validate([
            'owner_type' => ['required', 'in:usine,particulier'],
            'owner_id' => ['required', 'string', 'max:50'],
            'nom' => ['required', 'string', 'max:255'],
            'adresse' => ['nullable', 'string', 'max:255'],
        ], [
            'nom.required' => 'Le nom du site est obligatoire.',
            'owner_type.required' => 'Le type de client est obligatoire.',
            'owner_id.required' => 'Le client est obligatoire.',
        ]);
    }

    private function ownerRedirectUrl(string $ownerType, string|int $ownerId): string
    {
        if ($ownerType === 'usine') {
            return route('clients.usines.show', $ownerId);
        }

        return route('clients.show', $ownerId);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'prenoms' => ['nullable', 'string', 'max:150'],
            'contact' => ['nullable', 'string', 'max:50'],
            'adresse' => ['nullable', 'string', 'max:500'],
        ], [
            'nom.required' => 'Le nom est obligatoire.',
        ]);

        $client = Client::create([
            'code' => Client::prochainCode(),
            'nom' => trim($validated['nom']),
            'prenoms' => isset($validated['prenoms']) ? trim($validated['prenoms']) : null,
            'contact' => isset($validated['contact']) ? trim($validated['contact']) : null,
            'adresse' => isset($validated['adresse']) ? trim($validated['adresse']) : null,
        ]);

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Particulier ajouté avec succès.');
    }

    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'prenoms' => ['nullable', 'string', 'max:150'],
            'contact' => ['nullable', 'string', 'max:50'],
            'adresse' => ['nullable', 'string', 'max:500'],
        ], [
            'nom.required' => 'Le nom est obligatoire.',
        ]);

        $client->update([
            'nom' => trim($validated['nom']),
            'prenoms' => isset($validated['prenoms']) ? trim($validated['prenoms']) : null,
            'contact' => isset($validated['contact']) ? trim($validated['contact']) : null,
            'adresse' => isset($validated['adresse']) ? trim($validated['adresse']) : null,
        ]);

        return redirect()
            ->route('clients.show', $client)
            ->with('success', 'Particulier modifié avec succès.');
    }

    public function destroy(Client $client)
    {
        ClientSite::forOwner('particulier', $client->id)->delete();
        $client->delete();

        return redirect()
            ->route('clients.index', ['tab' => 'particuliers'])
            ->with('success', 'Particulier supprimé avec succès.');
    }

    private ?string $lastUsinesError = null;

    /**
     * @return array{id_usine: int|string, nom_usine: string, code_usine: ?string}|null
     */
    private function resolveUsineById(string $id): ?array
    {
        $usineLocale = Usine::query()->where('id_usine', $id)->first();
        if ($usineLocale) {
            return [
                'id_usine' => $usineLocale->id_usine,
                'nom_usine' => $usineLocale->nom_usine,
                'code_usine' => $usineLocale->code_usine,
            ];
        }

        foreach ($this->fetchUsines('') as $usine) {
            if ((string) ($usine['id_usine'] ?? '') === (string) $id) {
                return $usine;
            }
        }

        return null;
    }

    /**
     * @return list<array{id_usine: int|string|null, nom_usine: string, code_usine: ?string, source: string}>
     */
    private function fetchUsines(string $search): array
    {
        $this->lastUsinesError = null;
        $mesUsinesUrl = (string) config('services.external_auth.mes_usines_url');
        $timeout = (int) config('services.external_auth.timeout', 10);

        $usinesApi = [];

        if ($mesUsinesUrl !== '') {
            try {
                $queryParams = ['page' => 1];
                if ($search !== '') {
                    $queryParams['search'] = $search;
                }

                $response = Http::acceptJson()
                    ->withoutVerifying()
                    ->timeout($timeout)
                    ->get($mesUsinesUrl, $queryParams);

                if ($response->successful()) {
                    $pageUsines = $response->json('usines');
                    if (is_array($pageUsines)) {
                        $usinesApi = $pageUsines;
                    }

                    $pagination = $response->json('pagination');
                    $lastPage = (int) ($pagination['last_page'] ?? 1);

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
                } else {
                    $this->lastUsinesError = (string) ($response->json('error') ?? 'Erreur API usines.');
                }
            } catch (\Throwable $e) {
                $this->lastUsinesError = 'Impossible de joindre le service usines.';
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

        $usinesLocales = Usine::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('nom_usine', 'like', '%' . $search . '%')
                        ->orWhere('code_usine', 'like', '%' . $search . '%');
                });
            })
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
                'id_usine' => $usine->id_usine,
                'nom_usine' => $usine->nom_usine,
                'code_usine' => $usine->code_usine,
                'source' => 'local',
            ]);

        $usinesApiMapped = collect($usinesApi)
            ->map(fn ($u) => [
                'id_usine' => $u['id_usine'] ?? null,
                'nom_usine' => (string) ($u['nom_usine'] ?? ''),
                'code_usine' => $u['code_usine'] ?? null,
                'source' => 'api',
            ])
            ->filter(fn ($u) => $u['nom_usine'] !== '');

        return $usinesLocales
            ->concat($usinesApiMapped)
            ->sortBy(fn ($u) => mb_strtoupper((string) $u['nom_usine'], 'UTF-8'))
            ->values()
            ->all();
    }
}
