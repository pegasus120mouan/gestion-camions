<?php

namespace App\Http\Controllers;

use App\Models\Camion;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class CamionController extends Controller
{
    public function index(Request $request)
    {
        $timeout = 10;

        try {
            $response = Http::acceptJson()
                ->timeout($timeout)
                ->get('https://api.objetombrepegasus.online/api/camions/mes_camions.php');
        } catch (\Throwable $e) {
            return view('camions.index', [
                'camions' => new LengthAwarePaginator([], 0, 20),
                'chauffeurs' => collect(),
                'external_camions' => [],
                'external_error' => "Impossible de joindre le service camions.",
            ]);
        }

        if (! $response->successful()) {
            $message = (string) ($response->json('error') ?? 'Erreur API.');

            return view('camions.index', [
                'camions' => new LengthAwarePaginator([], 0, 20),
                'chauffeurs' => collect(),
                'external_camions' => [],
                'external_error' => $message,
            ]);
        }

        $vehicules = $response->json('vehicules');
        if (! is_array($vehicules)) {
            $vehicules = [];
        }

        // Filtrer par recherche si présente
        if ($request->filled('q')) {
            $q = strtolower($request->string('q')->toString());
            $vehicules = array_filter($vehicules, function ($v) use ($q) {
                $matricule = strtolower($v['matricule_vehicule'] ?? '');
                $type = strtolower($v['type_vehicule'] ?? '');
                return str_contains($matricule, $q) || str_contains($type, $q);
            });
            $vehicules = array_values($vehicules);
        }

        return view('camions.index', [
            'camions' => new LengthAwarePaginator([], 0, 20),
            'chauffeurs' => collect(),
            'external_camions' => $vehicules,
            'external_error' => null,
        ]);
    }

    public function show(Request $request, Camion $camion)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'data' => $camion->load(['chauffeur']),
            ]);
        }

        return view('camions.profile', [
            'camion' => $camion->load(['chauffeur']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'immatriculation' => ['required', 'string', 'max:255', 'unique:camions,immatriculation'],
            'marque' => ['nullable', 'string', 'max:255'],
            'modele' => ['nullable', 'string', 'max:255'],
            'annee' => ['nullable', 'integer', 'min:1900', 'max:2100'],
            'chauffeur_id' => ['nullable', 'integer', 'exists:users,id'],
            'actif' => ['nullable', 'boolean'],
            'image_face' => ['nullable', 'image', 'max:5120'],
            'image_profil_gauche' => ['nullable', 'image', 'max:5120'],
            'image_profil_droit' => ['nullable', 'image', 'max:5120'],
            'image_arriere' => ['nullable', 'image', 'max:5120'],
        ]);

        foreach (['image_face', 'image_profil_gauche', 'image_profil_droit', 'image_arriere'] as $k) {
            unset($validated[$k]);
        }

        $validated['actif'] = (bool) ($validated['actif'] ?? false);

        $prefix = 'CAM-';
        $stamp = Carbon::now()->format('YmdHis');

        do {
            $candidate = $prefix . $stamp . '-' . random_int(100, 999);
        } while (Camion::query()->where('reference', $candidate)->exists());

        $validated['reference'] = $candidate;

        $camion = Camion::create($validated);

        $disk = Storage::disk('s3');

        $updates = [];

        if (empty($camion->image_face) && !$request->hasFile('image_face')) {
            $updates['image_face'] = 'camions/camions.png';
        }

        if ($request->hasFile('image_face')) {
            $file = $request->file('image_face');
            $path = $disk->putFileAs("camions/{$camion->id}", $file, 'face.' . $file->getClientOriginalExtension());
            $updates['image_face'] = $path;
        }

        if ($request->hasFile('image_profil_gauche')) {
            $file = $request->file('image_profil_gauche');
            $path = $disk->putFileAs("camions/{$camion->id}", $file, 'profil_gauche.' . $file->getClientOriginalExtension());
            $updates['image_profil_gauche'] = $path;
        }

        if ($request->hasFile('image_profil_droit')) {
            $file = $request->file('image_profil_droit');
            $path = $disk->putFileAs("camions/{$camion->id}", $file, 'profil_droit.' . $file->getClientOriginalExtension());
            $updates['image_profil_droit'] = $path;
        }

        if ($request->hasFile('image_arriere')) {
            $file = $request->file('image_arriere');
            $path = $disk->putFileAs("camions/{$camion->id}", $file, 'arriere.' . $file->getClientOriginalExtension());
            $updates['image_arriere'] = $path;
        }

        if (!empty($updates)) {
            $camion->update($updates);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $camion->load(['chauffeur']),
            ], 201);
        }

        return redirect()->back();
    }

    public function edit(Request $request, Camion $camion)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'data' => $camion->load(['chauffeur']),
            ]);
        }

        $chauffeurs = User::query()->where('role', 'driver')->orderBy('name')->get();

        return view('camions.edit', [
            'camion' => $camion,
            'chauffeurs' => $chauffeurs,
        ]);
    }

    public function update(Request $request, Camion $camion)
    {
        $validated = $request->validate([
            'immatriculation' => ['sometimes', 'required', 'string', 'max:255', 'unique:camions,immatriculation,' . $camion->id],
            'marque' => ['sometimes', 'nullable', 'string', 'max:255'],
            'modele' => ['sometimes', 'nullable', 'string', 'max:255'],
            'annee' => ['sometimes', 'nullable', 'integer', 'min:1900', 'max:2100'],
            'chauffeur_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'actif' => ['nullable', 'boolean'],
            'image_face' => ['nullable', 'image', 'max:5120'],
            'image_profil_gauche' => ['nullable', 'image', 'max:5120'],
            'image_profil_droit' => ['nullable', 'image', 'max:5120'],
            'image_arriere' => ['nullable', 'image', 'max:5120'],
        ]);

        foreach (['image_face', 'image_profil_gauche', 'image_profil_droit', 'image_arriere'] as $k) {
            unset($validated[$k]);
        }

        $validated['actif'] = (bool) ($request->boolean('actif'));

        if (empty($camion->reference)) {
            $prefix = 'CAM-';
            $stamp = Carbon::now()->format('YmdHis');

            do {
                $candidate = $prefix . $stamp . '-' . random_int(100, 999);
            } while (Camion::query()->where('reference', $candidate)->exists());

            $validated['reference'] = $candidate;
        }

        $disk = Storage::disk('s3');

        if ($request->hasFile('image_face')) {
            if (!empty($camion->image_face)) {
                $disk->delete($camion->image_face);
            }
            $file = $request->file('image_face');
            $validated['image_face'] = $disk->putFileAs("camions/{$camion->id}", $file, 'face.' . $file->getClientOriginalExtension());
        }

        if ($request->hasFile('image_profil_gauche')) {
            if (!empty($camion->image_profil_gauche)) {
                $disk->delete($camion->image_profil_gauche);
            }
            $file = $request->file('image_profil_gauche');
            $validated['image_profil_gauche'] = $disk->putFileAs("camions/{$camion->id}", $file, 'profil_gauche.' . $file->getClientOriginalExtension());
        }

        if ($request->hasFile('image_profil_droit')) {
            if (!empty($camion->image_profil_droit)) {
                $disk->delete($camion->image_profil_droit);
            }
            $file = $request->file('image_profil_droit');
            $validated['image_profil_droit'] = $disk->putFileAs("camions/{$camion->id}", $file, 'profil_droit.' . $file->getClientOriginalExtension());
        }

        if ($request->hasFile('image_arriere')) {
            if (!empty($camion->image_arriere)) {
                $disk->delete($camion->image_arriere);
            }
            $file = $request->file('image_arriere');
            $validated['image_arriere'] = $disk->putFileAs("camions/{$camion->id}", $file, 'arriere.' . $file->getClientOriginalExtension());
        }

        $camion->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $camion->refresh()->load(['chauffeur']),
            ]);
        }

        return redirect()->back();
    }

    public function destroy(Camion $camion)
    {
        $camion->delete();

        if (request()->wantsJson()) {
            return response()->json(null, 204);
        }

        return redirect()->back();
    }
}
