<?php

namespace App\Http\Controllers;

use App\Models\CodeTransporteur;
use App\Models\CodeTransporteurVehicule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CodeTransporteurController extends Controller
{
    public function index()
    {
        $codes = CodeTransporteur::orderBy('nom')->get();
        
        return view('code_transporteurs.index', [
            'codes' => $codes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
        ]);

        CodeTransporteur::create($validated);

        return redirect()->route('code_transporteurs.index')
            ->with('success', 'Code transporteur ajouté avec succès.');
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:255'],
        ]);

        $code = CodeTransporteur::findOrFail($id);
        $code->update($validated);

        return redirect()->route('code_transporteurs.index')
            ->with('success', 'Code transporteur modifié avec succès.');
    }

    public function destroy(int $id)
    {
        $code = CodeTransporteur::findOrFail($id);
        $code->delete();

        return redirect()->route('code_transporteurs.index')
            ->with('success', 'Code transporteur supprimé avec succès.');
    }

    public function show(Request $request, int $id)
    {
        $code = CodeTransporteur::with('vehicules')->findOrFail($id);
        
        // Récupérer les véhicules depuis l'API mes_camions
        $mesCamionsUrl = (string) config('services.external_auth.mes_camions_url');
        $timeout = (int) config('services.external_auth.timeout', 10);
        $phpsessid = (string) $request->session()->get('external_auth.phpsessid', '');
        $vehiculesApi = [];
        
        try {
            $response = Http::acceptJson()
                ->timeout($timeout)
                ->withHeaders(['Cookie' => 'PHPSESSID=' . $phpsessid])
                ->get($mesCamionsUrl);
            if ($response->successful()) {
                $vehiculesApi = $response->json('vehicules') ?? [];
            }
        } catch (\Throwable $e) {}

        // Filtrer les véhicules déjà attribués
        $vehiculesAttribues = $code->vehicules->pluck('vehicule_id')->toArray();
        $vehiculesDisponibles = array_filter($vehiculesApi, function($v) use ($vehiculesAttribues) {
            return !in_array($v['vehicules_id'] ?? 0, $vehiculesAttribues);
        });

        return view('code_transporteurs.show', [
            'code' => $code,
            'vehiculesDisponibles' => array_values($vehiculesDisponibles),
        ]);
    }

    public function addVehicule(Request $request, int $id)
    {
        $validated = $request->validate([
            'vehicule_id' => ['required', 'integer'],
            'matricule_vehicule' => ['required', 'string'],
        ]);

        CodeTransporteurVehicule::create([
            'code_transporteur_id' => $id,
            'vehicule_id' => $validated['vehicule_id'],
            'matricule_vehicule' => $validated['matricule_vehicule'],
        ]);

        return redirect()->route('code_transporteurs.show', $id)
            ->with('success', 'Véhicule attribué avec succès.');
    }

    public function removeVehicule(int $id, int $vehicule_id)
    {
        CodeTransporteurVehicule::where('code_transporteur_id', $id)
            ->where('id', $vehicule_id)
            ->delete();

        return redirect()->route('code_transporteurs.show', $id)
            ->with('success', 'Véhicule retiré avec succès.');
    }
}
