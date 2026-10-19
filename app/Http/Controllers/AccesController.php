<?php

namespace App\Http\Controllers;

use App\Services\AccesPlateforme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccesController extends Controller
{
    public function index(AccesPlateforme $acces): View
    {
        return view('acces.index', [
            'roles' => $acces->rolesPourListe(),
        ]);
    }

    public function edit(string $role, AccesPlateforme $acces): View
    {
        $profil = $acces->rolePourEdition($role);
        abort_unless($profil !== null, 404);

        return view('acces.edit', [
            'role' => $profil,
        ]);
    }

    public function update(Request $request, string $role, AccesPlateforme $acces): RedirectResponse
    {
        abort_unless($acces->profilExiste($role), 404);

        $validated = $request->validate([
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string'],
        ]);

        $acces->enregistrerPermissions($role, $validated['permissions'] ?? []);

        return redirect()
            ->route('acces.index')
            ->with('success', 'Accès du rôle « ' . ($acces->rolePourEdition($role)['label'] ?? $role) . ' » enregistrés.');
    }
}
