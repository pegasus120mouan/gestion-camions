<?php

namespace App\Http\Controllers;

use App\Models\Produit;
use App\Models\Usine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ProduitController extends Controller
{
    public function index(Request $request)
    {
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

    public function edit(Request $request, Produit $produit)
    {
        if ($request->wantsJson()) {
            return response()->json(['data' => $produit]);
        }

        return view('produits.edit', [
            'produit' => $produit,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255', 'unique:produits,nom'],
            'tare' => ['required', 'numeric', 'min:0'],
        ]);

        // Normaliser le nom : première lettre majuscule, reste minuscule
        $validated['nom'] = $this->normaliserNom($validated['nom']);

        $produit = Produit::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['data' => $produit], 201);
        }

        return redirect()->back();
    }

    public function update(Request $request, Produit $produit)
    {
        $validated = $request->validate([
            'nom' => ['sometimes', 'required', 'string', 'max:255', 'unique:produits,nom,' . $produit->id],
            'tare' => ['sometimes', 'required', 'numeric', 'min:0'],
        ]);

        // Normaliser le nom si présent
        if (isset($validated['nom'])) {
            $validated['nom'] = $this->normaliserNom($validated['nom']);
        }

        $produit->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['data' => $produit->refresh()]);
        }

        return redirect()->back();
    }

    /**
     * Normalise le nom du produit : première lettre majuscule, reste minuscule
     * Exemple: "HEVEA" ou "hevea" ou "HeVeA" => "Hevea"
     */
    private function normaliserNom(string $nom): string
    {
        return mb_convert_case(trim($nom), MB_CASE_TITLE, 'UTF-8');
    }

    public function destroy(Produit $produit)
    {
        $produit->delete();

        if (request()->wantsJson()) {
            return response()->json(null, 204);
        }

        return redirect()->back();
    }
}
