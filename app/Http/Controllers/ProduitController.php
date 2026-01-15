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

        return redirect()->route('produits.edit', $produit);
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

        $produit->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['data' => $produit->refresh()]);
        }

        return redirect()->back();
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
