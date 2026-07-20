<?php

namespace App\Http\Controllers;

use App\Models\CamionEtat;
use App\Models\Camion;
use App\Models\FicheSortie;
use App\Models\Groupe;
use App\Models\GroupeVehicule;
use App\Models\Ticket;
use App\Models\TransporteurVehicule;
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
                ->withoutVerifying()
                ->timeout($timeout)
                ->get('https://api.objetombrepegasus.online/api/camions/mes_camions.php');
        } catch (\Throwable $e) {
            return view('camions.index', [
                'camions' => new LengthAwarePaginator([], 0, 20),
                'chauffeurs' => collect(),
                'external_camions' => [],
                'etats_par_vehicule' => [],
                'vehicules_en_cours' => [],
                'external_error' => "Impossible de joindre le service camions.",
            ]);
        }

        if (! $response->successful()) {
            $message = (string) ($response->json('error') ?? 'Erreur API.');

            return view('camions.index', [
                'camions' => new LengthAwarePaginator([], 0, 20),
                'chauffeurs' => collect(),
                'external_camions' => [],
                'etats_par_vehicule' => [],
                'vehicules_en_cours' => [],
                'external_error' => $message,
            ]);
        }

        $vehicules = $response->json('vehicules');
        if (! is_array($vehicules)) {
            $vehicules = [];
        }

        // Filtrer par recherche si un terme est fourni
        $search = $request->get('q');
        if ($search) {
            $search = strtolower(trim($search));
            $vehicules = array_filter($vehicules, function ($v) use ($search) {
                $matricule = strtolower($v['matricule_vehicule'] ?? '');
                return str_contains($matricule, $search);
            });
            $vehicules = array_values($vehicules); // Réindexer le tableau
        }

        $vehiculeIds = array_map(static fn ($v) => (int) ($v['vehicules_id'] ?? 0), $vehicules);
        $vehiculeIds = array_values(array_filter($vehiculeIds));
        $etatsParVehicule = [];
        foreach (array_chunk($vehiculeIds, 500) as $vehiculeIdsChunk) {
            $etatsParVehicule += CamionEtat::query()
                ->whereIn('vehicule_id', $vehiculeIdsChunk)
                ->pluck('etat', 'vehicule_id')
                ->toArray();
        }
        foreach ($etatsParVehicule as $vehiculeId => $etat) {
            if ($etat === 'inactif') {
                $etatsParVehicule[$vehiculeId] = 'en_panne';
            }
        }

        $vehiculesEnCours = [];
        foreach (array_chunk($vehiculeIds, 500) as $vehiculeIdsChunk) {
            $enCoursChunk = FicheSortie::query()
                ->whereIn('vehicule_id', $vehiculeIdsChunk)
                ->whereNull('date_dechargement')
                ->pluck('vehicule_id')
                ->map(static fn ($id) => (int) $id)
                ->toArray();

            foreach ($enCoursChunk as $vehiculeIdEnCours) {
                $vehiculesEnCours[$vehiculeIdEnCours] = true;
            }
        }

        // Filtrer par état (actif / en_panne / en_cours)
        $etatFiltre = (string) $request->query('etat', '');
        if (in_array($etatFiltre, ['actif', 'en_panne', 'en_cours'], true)) {
            $vehicules = array_filter($vehicules, function ($v) use ($etatFiltre, $vehiculesEnCours, $etatsParVehicule) {
                $vehiculeId = (int) ($v['vehicules_id'] ?? 0);
                $estEnCours = !empty($vehiculesEnCours[$vehiculeId]);
                $etat = $estEnCours ? 'en_cours' : ($etatsParVehicule[$vehiculeId] ?? 'actif');

                if ($etatFiltre === 'en_cours') {
                    return $etat === 'en_cours';
                }
                if ($etatFiltre === 'en_panne') {
                    return $etat === 'en_panne' || $etat === 'inactif';
                }

                // actif
                return $etat === 'actif';
            });
            $vehicules = array_values($vehicules);
        }

        return view('camions.index', [
            'camions' => new LengthAwarePaginator([], 0, 20),
            'chauffeurs' => collect(),
            'external_camions' => $vehicules,
            'etats_par_vehicule' => $etatsParVehicule,
            'vehicules_en_cours' => $vehiculesEnCours,
            'external_error' => null,
        ]);
    }

    public function updateVehiculeEtat(Request $request, int $vehiculeId)
    {
        $estEnCours = FicheSortie::query()
            ->where('vehicule_id', $vehiculeId)
            ->whereNull('date_dechargement')
            ->exists();

        if ($estEnCours) {
            return back()->with('error', "Etat verrouille : camion en cours d'utilisation.");
        }

        $validated = $request->validate([
            'etat' => ['required', 'in:actif,en_panne'],
            'matricule' => ['nullable', 'string', 'max:100'],
        ]);

        CamionEtat::updateOrCreate(
            ['vehicule_id' => $vehiculeId],
            [
                'matricule' => $validated['matricule'] ?? null,
                'etat' => $validated['etat'],
            ]
        );

        return back()->with('success', 'Etat du camion mis a jour.');
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

    public function camionsPgf(Request $request)
    {
        $vehicules = $this->fetchVehiculesApi();

        // Récupérer le groupe PGF (ou le créer s'il n'existe pas)
        $groupePgf = Groupe::firstOrCreate(['nom_groupe' => 'PGF']);

        // Récupérer les véhicules assignés au groupe PGF
        $vehiculesGroupePgf = GroupeVehicule::where('groupe_id', $groupePgf->id)->pluck('vehicule_id')->toArray();

        // Filtrer les véhicules qui appartiennent au groupe PGF
        $camionsPgf = array_filter($vehicules, function ($v) use ($vehiculesGroupePgf) {
            return in_array($v['vehicules_id'] ?? 0, $vehiculesGroupePgf);
        });
        $camionsPgf = array_values($camionsPgf);

        // Filtrer par recherche si présente
        if ($request->filled('q')) {
            $q = strtolower($request->string('q')->toString());
            $camionsPgf = array_filter($camionsPgf, function ($v) use ($q) {
                $matricule = strtolower($v['matricule_vehicule'] ?? '');
                $type = strtolower($v['type_vehicule'] ?? '');
                return str_contains($matricule, $q) || str_contains($type, $q);
            });
            $camionsPgf = array_values($camionsPgf);
        }

        $vehiculeIds = array_map(static fn ($v) => (int) ($v['vehicules_id'] ?? 0), $camionsPgf);
        $vehiculeIds = array_values(array_filter($vehiculeIds));

        $etatsParVehicule = [];
        foreach (array_chunk($vehiculeIds, 500) as $vehiculeIdsChunk) {
            $etatsParVehicule += CamionEtat::query()
                ->whereIn('vehicule_id', $vehiculeIdsChunk)
                ->pluck('etat', 'vehicule_id')
                ->toArray();
        }
        foreach ($etatsParVehicule as $vehiculeId => $etat) {
            if ($etat === 'inactif') {
                $etatsParVehicule[$vehiculeId] = 'en_panne';
            }
        }

        $vehiculesEnCours = [];
        foreach (array_chunk($vehiculeIds, 500) as $vehiculeIdsChunk) {
            $enCoursChunk = FicheSortie::query()
                ->whereIn('vehicule_id', $vehiculeIdsChunk)
                ->whereNull('date_dechargement')
                ->pluck('vehicule_id')
                ->map(static fn ($id) => (int) $id)
                ->toArray();

            foreach ($enCoursChunk as $vehiculeIdEnCours) {
                $vehiculesEnCours[$vehiculeIdEnCours] = true;
            }
        }

        return view('camions.camions_pgf', [
            'camions_pgf' => $camionsPgf,
            'groupe_pgf' => $groupePgf,
            'etats_par_vehicule' => $etatsParVehicule,
            'vehicules_en_cours' => $vehiculesEnCours,
        ]);
    }

    public function ajouterCamionsPgf(Request $request)
    {
        $vehicules = $this->fetchVehiculesApi();
        $groupePgf = Groupe::firstOrCreate(['nom_groupe' => 'PGF']);
        $vehiculesGroupePgf = GroupeVehicule::where('groupe_id', $groupePgf->id)
            ->pluck('vehicule_id')
            ->map(static fn ($id) => (int) $id)
            ->toArray();

        $vehiculesDisponibles = array_values(array_filter($vehicules, function ($v) use ($vehiculesGroupePgf) {
            return !in_array((int) ($v['vehicules_id'] ?? 0), $vehiculesGroupePgf, true);
        }));

        if ($request->filled('q')) {
            $q = strtolower($request->string('q')->toString());
            $vehiculesDisponibles = array_values(array_filter($vehiculesDisponibles, function ($v) use ($q) {
                $matricule = strtolower($v['matricule_vehicule'] ?? '');
                $type = strtolower($v['type_vehicule'] ?? '');

                return str_contains($matricule, $q) || str_contains($type, $q);
            }));
        }

        return view('camions.camions_pgf_ajouter', [
            'vehicules_disponibles' => $vehiculesDisponibles,
            'groupe_pgf' => $groupePgf,
            'total_disponibles' => count($vehiculesDisponibles),
        ]);
    }

    public function assignerGroupeBulk(Request $request)
    {
        $validated = $request->validate([
            'vehicule_ids' => ['required', 'array', 'min:1'],
            'vehicule_ids.*' => ['integer'],
            'matricules' => ['required', 'array'],
            'groupe_id' => ['required', 'integer', 'exists:groupes,id'],
        ], [
            'vehicule_ids.required' => 'Sélectionnez au moins un camion.',
            'vehicule_ids.min' => 'Sélectionnez au moins un camion.',
        ]);

        $count = 0;
        foreach ($validated['vehicule_ids'] as $vehiculeId) {
            $vehiculeId = (int) $vehiculeId;
            $matricule = trim((string) ($validated['matricules'][$vehiculeId] ?? ''));
            if ($vehiculeId <= 0 || $matricule === '') {
                continue;
            }

            GroupeVehicule::updateOrCreate(
                [
                    'vehicule_id' => $vehiculeId,
                    'groupe_id' => (int) $validated['groupe_id'],
                ],
                [
                    'matricule_vehicule' => $matricule,
                ]
            );

            TransporteurVehicule::query()->where('vehicule_id', $vehiculeId)->delete();
            $count++;
        }

        if ($count === 0) {
            return back()->with('error', 'Aucun camion valide n\'a pu être ajouté.');
        }

        $message = $count === 1
            ? '1 camion ajouté au groupe PGF avec succès.'
            : "{$count} camions ajoutés au groupe PGF avec succès.";

        return redirect()->route('camions.camions_pgf')->with('success', $message);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchVehiculesApi(): array
    {
        try {
            $response = Http::acceptJson()
                ->withoutVerifying()
                ->timeout(10)
                ->get('https://api.objetombrepegasus.online/api/camions/mes_camions.php');
            if ($response->successful()) {
                return $response->json('vehicules') ?? [];
            }
        } catch (\Throwable $e) {
        }

        return [];
    }

    public function assignerGroupe(Request $request)
    {
        $validated = $request->validate([
            'vehicule_id' => ['required', 'integer'],
            'matricule_vehicule' => ['required', 'string', 'max:50'],
            'groupe_id' => ['required', 'integer', 'exists:groupes,id'],
        ]);

        GroupeVehicule::updateOrCreate(
            [
                'vehicule_id' => $validated['vehicule_id'],
                'groupe_id' => $validated['groupe_id'],
            ],
            [
                'matricule_vehicule' => $validated['matricule_vehicule'],
            ]
        );

        return back()->with('success', 'Véhicule assigné au groupe avec succès.');
    }

    public function retirerGroupe(Request $request, int $vehiculeId)
    {
        $groupeId = $request->input('groupe_id');

        GroupeVehicule::where('vehicule_id', $vehiculeId)
            ->where('groupe_id', $groupeId)
            ->delete();

        return back()->with('success', 'Véhicule retiré du groupe avec succès.');
    }

    /**
     * Revenus des camions du groupe PGF (vue type montant transporteur).
     */
    public function revenuesPgf()
    {
        $montants = $this->calculerMontantsPgf();

        $data = [[
            'nom' => 'PGF',
            'prenoms' => '',
            'code' => 'PGF',
            'camions_count' => $montants['camions_count'],
            'montant_du' => $montants['montant_du'],
            'montant_paye' => $montants['montant_paye'],
            'reste_a_payer' => $montants['reste_a_payer'],
        ]];

        return view('camions.revenues_pgf', [
            'data' => $data,
        ]);
    }

    /**
     * Situation financière détaillée des camions PGF.
     */
    public function revenuesPgfShow(Request $request)
    {
        $filtres = [
            'vehicule' => trim((string) $request->query('vehicule', '')),
            'date_debut' => $request->query('date_debut'),
            'date_fin' => $request->query('date_fin'),
        ];

        $vehiculesPgf = $this->vehiculesPgf();
        $vehiculeIds = $vehiculesPgf->pluck('vehicule_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $matricules = $vehiculesPgf->pluck('matricule_vehicule')
            ->map(fn ($m) => strtoupper(trim((string) $m)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $montants = $this->calculerMontantsPgf($filtres);

        $fichesQuery = FicheSortie::query()
            ->when($vehiculeIds !== [], fn ($q) => $q->whereIn('vehicule_id', $vehiculeIds), fn ($q) => $q->whereRaw('1 = 0'))
            ->orderByDesc('date_chargement')
            ->orderByDesc('id');

        if ($filtres['vehicule'] !== '') {
            $fichesQuery->where(function ($q) use ($filtres) {
                $q->where('matricule_vehicule', $filtres['vehicule'])
                    ->orWhere('vehicule_id', (int) $filtres['vehicule']);
            });
        }
        if (! empty($filtres['date_debut'])) {
            $fichesQuery->whereDate('date_chargement', '>=', $filtres['date_debut']);
        }
        if (! empty($filtres['date_fin'])) {
            $fichesQuery->whereDate('date_chargement', '<=', $filtres['date_fin']);
        }

        $fiches = $fichesQuery->limit(200)->get();

        $ticketsQuery = Ticket::query()
            ->when(
                $vehiculeIds !== [] || $matricules !== [],
                function ($q) use ($vehiculeIds, $matricules) {
                    $q->where(function ($inner) use ($vehiculeIds, $matricules) {
                        if ($vehiculeIds !== []) {
                            $inner->whereIn('vehicule_id', $vehiculeIds);
                        }
                        if ($matricules !== []) {
                            $inner->orWhereIn('matricule_vehicule', $matricules);
                        }
                    });
                },
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->orderByDesc('date_ticket')
            ->orderByDesc('id_ticket');

        if ($filtres['vehicule'] !== '') {
            $ticketsQuery->where(function ($q) use ($filtres) {
                $q->where('matricule_vehicule', $filtres['vehicule'])
                    ->orWhere('vehicule_id', (int) $filtres['vehicule']);
            });
        }
        if (! empty($filtres['date_debut'])) {
            $ticketsQuery->whereDate('date_ticket', '>=', $filtres['date_debut']);
        }
        if (! empty($filtres['date_fin'])) {
            $ticketsQuery->whereDate('date_ticket', '<=', $filtres['date_fin']);
        }

        $tickets = $ticketsQuery->limit(200)->get();

        $usinesById = \App\Models\Usine::query()
            ->get(['id_usine', 'nom_usine'])
            ->pluck('nom_usine', 'id_usine')
            ->all();

        $fichesByTicketId = FicheSortie::query()
            ->whereIn('id_ticket', $tickets->pluck('id_ticket')->filter()->all())
            ->get()
            ->keyBy('id_ticket');

        $ticketsDetails = $tickets->map(function ($ticket) use ($usinesById, $fichesByTicketId) {
            $idTicket = (int) $ticket->id_ticket;
            $fiche = $fichesByTicketId->get($idTicket);
            $poids = (float) ($ticket->poids ?? 0);
            $prix = $ticket->prix_unitaire !== null ? (float) $ticket->prix_unitaire : null;
            $montant = $ticket->montant_paie !== null
                ? (float) $ticket->montant_paie
                : (($prix !== null && $poids > 0) ? $prix * $poids : null);
            $statut = mb_strtolower(trim((string) ($ticket->statut_ticket ?? '')), 'UTF-8');
            $estPaye = in_array($statut, ['soldé', 'solde', 'payé', 'paye'], true);

            return [
                'id_ticket' => $idTicket,
                'date_ticket' => $ticket->date_ticket,
                'numero_ticket' => $ticket->numero_ticket,
                'nom_usine' => $usinesById[(int) ($ticket->id_usine ?? 0)] ?? '—',
                'nom_agent' => $fiche?->nom_agent ?: '—',
                'nom_pont' => $fiche?->nom_pont ?: '—',
                'vehicule_id' => (int) ($ticket->vehicule_id ?? 0),
                'matricule_vehicule' => $ticket->matricule_vehicule ?: '—',
                'poids' => $poids,
                'prix_unitaire' => $prix,
                'montant' => $montant,
                'est_paye' => $estPaye,
            ];
        })->values();

        $camionsDetails = $vehiculesPgf->map(function ($vehicule) {
            $id = (int) $vehicule->vehicule_id;
            $matricule = strtoupper(trim((string) $vehicule->matricule_vehicule));

            $montantDu = (float) FicheSortie::query()
                ->where('vehicule_id', $id)
                ->sum('montant_camion');
            $montantPayeFiche = (float) FicheSortie::query()
                ->where('vehicule_id', $id)
                ->sum('montant_paye_transporteur');

            $ticketsVehicule = Ticket::query()
                ->where(function ($q) use ($id, $matricule) {
                    $q->where('vehicule_id', $id);
                    if ($matricule !== '') {
                        $q->orWhere('matricule_vehicule', $matricule);
                    }
                })
                ->get(['montant_paie', 'statut_ticket']);

            $montantPayeTickets = (float) $ticketsVehicule
                ->filter(function ($t) {
                    $statut = mb_strtolower(trim((string) ($t->statut_ticket ?? '')), 'UTF-8');

                    return in_array($statut, ['soldé', 'solde', 'payé', 'paye'], true)
                        && (float) ($t->montant_paie ?? 0) > 0;
                })
                ->sum('montant_paie');

            if ($montantDu <= 0) {
                $montantDu = (float) $ticketsVehicule
                    ->filter(fn ($t) => (float) ($t->montant_paie ?? 0) > 0)
                    ->sum('montant_paie');
            }

            $montantPaye = $montantPayeFiche + $montantPayeTickets;

            return [
                'vehicule_id' => $id,
                'matricule' => $vehicule->matricule_vehicule,
                'nb_fiches' => FicheSortie::query()->where('vehicule_id', $id)->count(),
                'montant_du' => (int) round($montantDu),
                'montant_paye' => (int) round($montantPaye),
                'reste_a_payer' => (int) round($montantDu - $montantPaye),
            ];
        })->values();

        return view('camions.revenues_pgf_show', [
            'montantDu' => $montants['montant_du'],
            'montantPaye' => $montants['montant_paye'],
            'resteAPayer' => $montants['reste_a_payer'],
            'camionsCount' => $montants['camions_count'],
            'vehiculesPgf' => $vehiculesPgf,
            'camionsDetails' => $camionsDetails,
            'fiches' => $fiches,
            'tickets' => $ticketsDetails,
            'filtres' => $filtres,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, GroupeVehicule>
     */
    private function vehiculesPgf()
    {
        $groupeIds = Groupe::query()
            ->where('nom_groupe', 'like', '%PGF%')
            ->pluck('id');

        return GroupeVehicule::query()
            ->whereIn('groupe_id', $groupeIds)
            ->orderBy('matricule_vehicule')
            ->get();
    }

    /**
     * @param  array{vehicule?: string, date_debut?: string|null, date_fin?: string|null}  $filtres
     * @return array{camions_count: int, montant_du: int, montant_paye: int, reste_a_payer: int}
     */
    private function calculerMontantsPgf(array $filtres = []): array
    {
        $vehiculesPgf = $this->vehiculesPgf();
        $vehiculeIds = $vehiculesPgf->pluck('vehicule_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();
        $matricules = $vehiculesPgf->pluck('matricule_vehicule')
            ->map(fn ($m) => strtoupper(trim((string) $m)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $vehiculeFiltre = trim((string) ($filtres['vehicule'] ?? ''));
        $dateDebut = $filtres['date_debut'] ?? null;
        $dateFin = $filtres['date_fin'] ?? null;

        $fichesQuery = FicheSortie::query()
            ->when($vehiculeIds !== [], fn ($q) => $q->whereIn('vehicule_id', $vehiculeIds), fn ($q) => $q->whereRaw('1 = 0'));

        if ($vehiculeFiltre !== '') {
            $fichesQuery->where(function ($q) use ($vehiculeFiltre) {
                $q->where('matricule_vehicule', $vehiculeFiltre)
                    ->orWhere('vehicule_id', (int) $vehiculeFiltre);
            });
        }
        if (! empty($dateDebut)) {
            $fichesQuery->whereDate('date_chargement', '>=', $dateDebut);
        }
        if (! empty($dateFin)) {
            $fichesQuery->whereDate('date_chargement', '<=', $dateFin);
        }

        $montantDuFiches = (float) (clone $fichesQuery)->sum('montant_camion');
        $montantPayeFiches = (float) (clone $fichesQuery)->sum('montant_paye_transporteur');

        $ticketsQuery = Ticket::query();
        if ($vehiculeIds !== [] || $matricules !== []) {
            $ticketsQuery->where(function ($q) use ($vehiculeIds, $matricules) {
                if ($vehiculeIds !== []) {
                    $q->whereIn('vehicule_id', $vehiculeIds);
                }
                if ($matricules !== []) {
                    $q->orWhereIn('matricule_vehicule', $matricules);
                }
            });
        } else {
            $ticketsQuery->whereRaw('1 = 0');
        }

        if ($vehiculeFiltre !== '') {
            $ticketsQuery->where(function ($q) use ($vehiculeFiltre) {
                $q->where('matricule_vehicule', $vehiculeFiltre)
                    ->orWhere('vehicule_id', (int) $vehiculeFiltre);
            });
        }
        if (! empty($dateDebut)) {
            $ticketsQuery->whereDate('date_ticket', '>=', $dateDebut);
        }
        if (! empty($dateFin)) {
            $ticketsQuery->whereDate('date_ticket', '<=', $dateFin);
        }

        $tickets = $ticketsQuery->get(['montant_paie', 'statut_ticket']);
        $montantDuTickets = (float) $tickets
            ->filter(fn ($t) => (float) ($t->montant_paie ?? 0) > 0)
            ->sum('montant_paie');
        $montantPayeTickets = (float) $tickets
            ->filter(function ($t) {
                $statut = mb_strtolower(trim((string) ($t->statut_ticket ?? '')), 'UTF-8');

                return in_array($statut, ['soldé', 'solde', 'payé', 'paye'], true)
                    && (float) ($t->montant_paie ?? 0) > 0;
            })
            ->sum('montant_paie');

        $montantDu = $montantDuFiches > 0 ? $montantDuFiches : $montantDuTickets;
        $montantPaye = $montantDuFiches > 0
            ? ($montantPayeFiches + $montantPayeTickets)
            : $montantPayeTickets;

        return [
            'camions_count' => $vehiculesPgf->count(),
            'montant_du' => (int) round($montantDu),
            'montant_paye' => (int) round($montantPaye),
            'reste_a_payer' => (int) round($montantDu - $montantPaye),
        ];
    }
}
