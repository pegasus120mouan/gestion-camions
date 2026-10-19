<?php

namespace App\Http\Controllers;

use App\Models\ParticulierAgent;
use App\Models\Produit;
use App\Models\Usine;
use App\Services\AccesPlateforme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

class ProduitController extends Controller
{
    public function index(Request $request, AccesPlateforme $acces)
    {
        abort_unless($acces->peut('produits.voir', 'produits.creer', 'produits.modifier', 'produits.supprimer'), 403);

        $query = Produit::query()->latest();

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where('nom', 'like', "%{$q}%");
        }

        $produits = $query->paginate(20)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json(['data' => $produits]);
        }

        return view('produits.index', [
            'produits' => $produits,
            'peutCreerProduit' => $acces->peut('produits.creer'),
            'peutModifierProduit' => $acces->peut('produits.modifier'),
            'peutSupprimerProduit' => $acces->peut('produits.supprimer'),
            'profilAcces' => $acces->profilCourant($request),
        ]);
    }

    public function show(Request $request, Produit $produit)
    {
        if ($request->wantsJson()) {
            return response()->json(['data' => $produit]);
        }

        // Récupérer les fiches de sortie associées à ce produit
        $fichesSortie = \App\Models\FicheSortie::where('produit_id', $produit->id)
            ->orderBy('date_chargement', 'desc')
            ->paginate(20);

        // Construire un index usinesById : ID → nom (locales + API)
        $usinesById = [];
        foreach (Usine::all() as $ul) {
            $usinesById[(string) $ul->id_usine] = $ul->nom_usine;
        }
        try {
            $timeout = (int) config('services.external_auth.timeout', 10);
            $usinesUrl = (string) config('services.external_auth.mes_usines_url');
            $resp = Http::acceptJson()->withoutVerifying()->timeout($timeout)->get($usinesUrl);
            if ($resp->successful()) {
                foreach ($resp->json('usines') ?? [] as $u) {
                    $key = (string) ($u['id_usine'] ?? '');
                    if ($key !== '' && !isset($usinesById[$key])) {
                        $usinesById[$key] = $u['nom_usine'] ?? '';
                    }
                }
            }
        } catch (\Throwable $e) {}

        // Construire un index agentsById : id_agent API → nom_complet (depuis particulier_agents)
        $agentsById = ParticulierAgent::all()->keyBy('id_agent')
            ->map(fn ($a) => $a->nom_complet);

        // Enrichir chaque fiche : résoudre usine (si numérique) et nom_agent (si vide)
        foreach ($fichesSortie as $fiche) {
            if (is_numeric($fiche->usine) && isset($usinesById[$fiche->usine])) {
                $fiche->usine = $usinesById[$fiche->usine];
            }
            if (empty($fiche->nom_agent) && !empty($fiche->id_agent)) {
                $fiche->nom_agent = $agentsById[(string) $fiche->id_agent] ?? '-';
            }
        }

        $usines = Schema::hasColumn('usines', 'produit_id')
            ? $produit->usines()->orderBy('nom_usine')->get()
            : collect();

        return view('produits.show', [
            'produit' => $produit,
            'fichesSortie' => $fichesSortie,
            'usines' => $usines,
            'prochainCodeUsine' => Schema::hasColumn('usines', 'produit_id')
                ? $this->genererCodeUsine($produit)
                : null,
        ]);
    }

    public function storeUsine(Request $request, Produit $produit)
    {
        if (!Schema::hasColumn('usines', 'produit_id')) {
            return redirect()->route('produits.show', $produit)->withErrors([
                'nom_usine' => "La migration des usines n'a pas encore été exécutée.",
            ]);
        }

        $validated = $request->validate([
            'nom_usine' => ['required', 'string', 'max:255'],
        ]);

        $nomUsine = mb_convert_case(trim($validated['nom_usine']), MB_CASE_UPPER, 'UTF-8');

        if (Usine::where('nom_usine', $nomUsine)->where('produit_id', $produit->id)->exists()) {
            return redirect()->route('produits.show', $produit)
                ->withInput()
                ->withErrors(['nom_usine' => 'Cette usine est déjà attribuée à ce produit.']);
        }

        Usine::create([
            'nom_usine' => $nomUsine,
            'code_usine' => $this->genererCodeUsine($produit),
            'produit_id' => $produit->id,
        ]);

        return redirect()->route('produits.show', $produit)->with('success', 'Usine ajoutée au produit avec succès.');
    }

    public function destroyUsine(Produit $produit, Usine $usine)
    {
        abort_unless((int) $usine->produit_id === (int) $produit->id, 404);
        abort_unless(Usine::isLocalId((int) $usine->id_usine), 403);

        $nom = (string) $usine->nom_usine;
        $usine->delete();

        \Illuminate\Support\Facades\Cache::forget('usines_api_enrichies');
        \Illuminate\Support\Facades\Cache::forget('usines_api_enrichies_v2');

        return redirect()
            ->route('produits.show', $produit)
            ->with('success', 'Usine « ' . $nom . ' » retirée du produit.');
    }

    private function genererCodeUsine(Produit $produit): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Z0-9]/u', '', mb_substr($produit->nom, 0, 3)));
        if ($prefix === '') {
            $prefix = 'USN';
        }

        $codes = Usine::query()
            ->where('produit_id', $produit->id)
            ->whereNotNull('code_usine')
            ->pluck('code_usine');

        $max = 0;
        foreach ($codes as $code) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/', (string) $code, $matches)) {
                $max = max($max, (int) $matches[1]);
            }
        }

        return $prefix . '-' . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }

    public function edit(Request $request, Produit $produit, AccesPlateforme $acces)
    {
        abort_unless($acces->peut('produits.modifier'), 403);

        if ($request->wantsJson()) {
            return response()->json(['data' => $produit]);
        }

        return view('produits.edit', [
            'produit' => $produit,
        ]);
    }

    public function store(Request $request, AccesPlateforme $acces)
    {
        abort_unless($acces->peut('produits.creer'), 403);

        $request->merge([
            'nom' => $this->normaliserNom((string) $request->input('nom', '')),
        ]);

        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255', 'unique:produits,nom'],
            'tare' => ['required', 'numeric', 'min:0'],
        ]);

        $produit = Produit::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['data' => $produit], 201);
        }

        return redirect()
            ->route('produits.index')
            ->with('success', 'Produit « ' . $produit->nom . ' » créé.');
    }

    public function update(Request $request, Produit $produit, AccesPlateforme $acces)
    {
        abort_unless($acces->peut('produits.modifier'), 403);

        $request->merge([
            'nom' => $this->normaliserNom((string) $request->input('nom', $produit->nom)),
        ]);

        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255', 'unique:produits,nom,' . $produit->id],
            'tare' => ['required', 'numeric', 'min:0'],
        ]);

        $produit->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['data' => $produit->refresh()]);
        }

        return redirect()
            ->route('produits.index')
            ->with('success', 'Produit « ' . $produit->nom . ' » modifié.');
    }

    /**
     * Normalise le nom du produit : première lettre de chaque mot en majuscule.
     * Exemple: "HEVEA" ou "hevea" => "Hevea"
     */
    private function normaliserNom(string $nom): string
    {
        return mb_convert_case(trim($nom), MB_CASE_TITLE, 'UTF-8');
    }

    public function destroy(Request $request, Produit $produit, AccesPlateforme $acces)
    {
        abort_unless($acces->peut('produits.supprimer'), 403);

        $nom = $produit->nom;

        try {
            $produit->delete();
        } catch (\Throwable $e) {
            $message = 'Impossible de supprimer « ' . $nom . ' » : ce produit est encore utilisé.';

            if ($request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return redirect()
                ->route('produits.index')
                ->withErrors(['delete' => $message]);
        }

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return redirect()
            ->route('produits.index')
            ->with('success', 'Produit « ' . $nom . ' » supprimé.');
    }
}
