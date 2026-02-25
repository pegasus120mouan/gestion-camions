<?php

namespace App\Http\Controllers;

use App\Models\ChefChargeur;
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
        ]);

        $chefChargeur->update($validated);

        return redirect()->route('chef_chargeurs.index')->with('success', 'Chef des chargeurs modifié avec succès.');
    }

    public function destroy(ChefChargeur $chefChargeur)
    {
        $chefChargeur->delete();
        return redirect()->route('chef_chargeurs.index')->with('success', 'Chef des chargeurs supprimé avec succès.');
    }
}
