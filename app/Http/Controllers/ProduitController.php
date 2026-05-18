<?php

namespace App\Http\Controllers;

use App\Models\Produit;
use Illuminate\Http\Request;

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

        return view('produits.show', [
            'produit' => $produit,
            'fichesSortie' => $fichesSortie,
        ]);
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
