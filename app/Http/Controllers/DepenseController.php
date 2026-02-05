<?php

namespace App\Http\Controllers;

use App\Models\Depense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class DepenseController extends Controller
{
    public function index(Request $request, int $vehiculeId)
    {
        $authUser = Auth::user();

        if (!$authUser || $authUser->role !== 'proprietaire') {
            return view('depenses.index', [
                'depenses' => collect(),
                'vehicule' => null,
                'vehicule_id' => $vehiculeId,
                'external_error' => "Accès réservé aux propriétaires.",
            ]);
        }

        $matricule = (string) $request->query('matricule', '');

        $depenses = Depense::where('vehicule_id', $vehiculeId)
            ->orderBy('date_depense', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20);

        $displayMatricule = $matricule;
        if (!$displayMatricule) {
            $existingDepense = Depense::where('vehicule_id', $vehiculeId)->first();
            $displayMatricule = $existingDepense?->matricule_vehicule ?: '';
        }

        return view('depenses.index', [
            'depenses' => $depenses,
            'vehicule' => [
                'vehicules_id' => $vehiculeId,
                'matricule_vehicule' => $displayMatricule,
            ],
            'vehicule_id' => $vehiculeId,
            'external_error' => null,
        ]);
    }

    public function store(Request $request, int $vehiculeId)
    {
        $authUser = Auth::user();

        if (!$authUser || $authUser->role !== 'proprietaire') {
            return back()->withErrors(['error' => "Accès réservé aux propriétaires."]);
        }

        $validated = $request->validate([
            'type_depense' => ['required', 'string', 'max:100'],
            'matricule_vehicule' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'montant' => ['required', 'numeric', 'min:0'],
            'date_depense' => ['required', 'date'],
        ]);

        Depense::create([
            'vehicule_id' => $vehiculeId,
            'matricule_vehicule' => $validated['matricule_vehicule'],
            'type_depense' => $validated['type_depense'],
            'description' => $validated['description'] ?? '',
            'montant' => $validated['montant'],
            'date_depense' => $validated['date_depense'],
        ]);

        return back()->with('success', 'Dépense enregistrée avec succès.');
    }
}
