<?php

namespace App\Http\Controllers;

use App\Models\ChefChargeur;
use App\Models\ChefChargeurPrix;
use App\Models\Produit;
use Illuminate\Http\Request;

class ChefChargeurController extends Controller
{
    public function index()
    {
        $chefChargeurs = ChefChargeur::orderBy('nom')->paginate(20);
        return view('chef_chargeurs.index', compact('chefChargeurs'));
    }

    public function create()
    {
        return view('chef_chargeurs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'prenoms' => ['required', 'string', 'max:150'],
            'contact' => ['nullable', 'string', 'max:50'],
            'prix_unitaire' => ['nullable', 'integer', 'min:0'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date'],
        ]);

        ChefChargeur::create($validated);

        return redirect()->route('chef_chargeurs.index')->with('success', 'Chef des chargeurs créé avec succès.');
    }

    public function edit(ChefChargeur $chefChargeur)
    {
        return view('chef_chargeurs.edit', compact('chefChargeur'));
    }

    public function update(Request $request, ChefChargeur $chefChargeur)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'prenoms' => ['required', 'string', 'max:150'],
            'contact' => ['nullable', 'string', 'max:50'],
            'prix_unitaire' => ['nullable', 'integer', 'min:0'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date'],
        ]);

        $chefChargeur->update($validated);

        return redirect()->route('chef_chargeurs.index')->with('success', 'Chef des chargeurs modifié avec succès.');
    }

    public function destroy(ChefChargeur $chefChargeur)
    {
        $chefChargeur->delete();
        return redirect()->route('chef_chargeurs.index')->with('success', 'Chef des chargeurs supprimé avec succès.');
    }

    public function show(ChefChargeur $chefChargeur)
    {
        $chefChargeur->load(['chargeurs', 'prixPeriodes.produit']);
        $produits = Produit::query()->orderBy('nom')->get();

        return view('chef_chargeurs.show', [
            'chef' => $chefChargeur,
            'produits' => $produits,
        ]);
    }

    public function storePrix(Request $request, ChefChargeur $chefChargeur)
    {
        $validated = $request->validate([
            'produit_id' => ['required', 'integer', 'exists:produits,id'],
            'prix_unitaire' => ['required', 'integer', 'min:0'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
        ]);

        $produit = Produit::query()->findOrFail($validated['produit_id']);
        $validated['nom_produit'] = $produit->nom;

        if ($this->periodeChevauche($chefChargeur, $validated['produit_id'], $validated['date_debut'], $validated['date_fin'] ?? null)) {
            return redirect()->route('chef_chargeurs.show', $chefChargeur)
                ->with('error', 'Cette période chevauche une période existante pour ce produit.');
        }

        $chefChargeur->prixPeriodes()->create($validated);

        return redirect()->route('chef_chargeurs.show', $chefChargeur)->with('success', 'Prix ajouté avec succès.');
    }

    public function updatePrix(Request $request, ChefChargeur $chefChargeur, ChefChargeurPrix $prix)
    {
        $validated = $request->validate([
            'produit_id' => ['required', 'integer', 'exists:produits,id'],
            'prix_unitaire' => ['required', 'integer', 'min:0'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
        ]);

        $produit = Produit::query()->findOrFail($validated['produit_id']);
        $validated['nom_produit'] = $produit->nom;

        if ($this->periodeChevauche(
            $chefChargeur,
            $validated['produit_id'],
            $validated['date_debut'],
            $validated['date_fin'] ?? null,
            $prix->id
        )) {
            return redirect()->route('chef_chargeurs.show', $chefChargeur)
                ->with('error', 'Cette période chevauche une période existante pour ce produit.');
        }

        $prix->update($validated);

        return redirect()->route('chef_chargeurs.show', $chefChargeur)->with('success', 'Prix modifié avec succès.');
    }

    public function destroyPrix(ChefChargeur $chefChargeur, ChefChargeurPrix $prix)
    {
        $prix->delete();

        return redirect()->route('chef_chargeurs.show', $chefChargeur)->with('success', 'Prix supprimé avec succès.');
    }

    private function periodeChevauche(
        ChefChargeur $chefChargeur,
        int $produitId,
        string $dateDebut,
        ?string $dateFin,
        ?int $ignoreId = null
    ): bool {
        return $chefChargeur->prixPeriodes()
            ->where('produit_id', $produitId)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where(function ($query) use ($dateDebut, $dateFin) {
                $query->where(function ($q) use ($dateDebut) {
                    $q->where('date_debut', '<=', $dateDebut)
                        ->where(function ($q2) use ($dateDebut) {
                            $q2->whereNull('date_fin')
                                ->orWhere('date_fin', '>=', $dateDebut);
                        });
                })->orWhere(function ($q) use ($dateDebut, $dateFin) {
                    if ($dateFin) {
                        $q->where('date_debut', '<=', $dateFin)
                            ->where('date_debut', '>=', $dateDebut);
                    } else {
                        $q->where('date_debut', '>=', $dateDebut);
                    }
                });
            })
            ->exists();
    }
}
