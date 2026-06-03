<?php

namespace App\Http\Controllers;

use App\Models\ParticulierAgent;
use App\Models\ParticulierAgentPrix;
use App\Models\ParticulierGroupe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ParticulierPrixController extends Controller
{
    private function fetchUsines(): array
    {
        $mesUsinesUrl = (string) config('services.external_auth.mes_usines_url');
        $timeout = (int) config('services.external_auth.timeout', 10);

        try {
            $response = Http::acceptJson()
                ->withoutVerifying()
                ->timeout($timeout)
                ->get($mesUsinesUrl);

            if ($response->successful()) {
                return $response->json('usines') ?? [];
            }
        } catch (\Throwable $e) {
        }

        return [];
    }

    public function index(Request $request)
    {
        $query = ParticulierAgent::with(['groupe'])
            ->withCount('prix')
            ->orderBy('nom')
            ->orderBy('prenoms');

        if ($request->filled('particulier_groupe_id')) {
            $query->where('particulier_groupe_id', (int) $request->input('particulier_groupe_id'));
        }

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($sub) use ($q) {
                $sub->where('numero_agent', 'like', "%{$q}%")
                    ->orWhere('nom', 'like', "%{$q}%")
                    ->orWhere('prenoms', 'like', "%{$q}%")
                    ->orWhere('contact', 'like', "%{$q}%")
                    ->orWhereHas('groupe', function ($groupeQuery) use ($q) {
                        $groupeQuery->where('nom_groupe', 'like', "%{$q}%");
                    });
            });
        }

        return view('particuliers.prix.index', [
            'agents' => $query->paginate(20)->withQueryString(),
            'groupes' => ParticulierGroupe::orderBy('nom_groupe')->get(),
            'search' => trim((string) $request->query('q', '')),
            'agentNoms' => ParticulierAgent::query()
                ->orderBy('nom')
                ->orderBy('prenoms')
                ->get()
                ->map(fn ($a) => $a->nom_complet)
                ->unique()
                ->values(),
        ]);
    }

    public function show(ParticulierAgent $agent)
    {
        $agent->load(['groupe', 'prix' => fn ($q) => $q->orderBy('nom_usine')]);

        return view('particuliers.prix.show', [
            'agent' => $agent,
            'usines' => $this->fetchUsines(),
        ]);
    }

    public function storePrix(Request $request, ParticulierAgent $agent)
    {
        $validated = $request->validate([
            'id_usine' => ['required'],
            'nom_usine' => ['required', 'string', 'max:255'],
            'prix' => ['required', 'numeric', 'min:0'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
            'toutes_usines' => ['nullable', 'in:0,1'],
        ]);

        if ($request->input('toutes_usines') === '1' || $validated['id_usine'] === 'all') {
            $usines = $this->fetchUsines();
            $count = 0;

            foreach ($usines as $usine) {
                $idUsine = (int) ($usine['id_usine'] ?? 0);
                if ($idUsine <= 0) {
                    continue;
                }

                $exists = ParticulierAgentPrix::where('particulier_agent_id', $agent->id)
                    ->where('id_usine', $idUsine)
                    ->exists();

                if (!$exists) {
                    ParticulierAgentPrix::create([
                        'particulier_agent_id' => $agent->id,
                        'id_usine' => $idUsine,
                        'nom_usine' => $usine['nom_usine'] ?? '',
                        'prix' => $validated['prix'],
                        'date_debut' => $validated['date_debut'] ?? null,
                        'date_fin' => $validated['date_fin'] ?? null,
                    ]);
                    $count++;
                }
            }

            return redirect()->route('particuliers.prix.show', $agent)
                ->with('success', "Prix ajouté pour {$count} usine(s).");
        }

        $exists = ParticulierAgentPrix::where('particulier_agent_id', $agent->id)
            ->where('id_usine', $validated['id_usine'])
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors([
                'id_usine' => 'Un prix existe déjà pour cette usine.',
            ]);
        }

        ParticulierAgentPrix::create([
            'particulier_agent_id' => $agent->id,
            'id_usine' => $validated['id_usine'],
            'nom_usine' => $validated['nom_usine'],
            'prix' => $validated['prix'],
            'date_debut' => $validated['date_debut'] ?? null,
            'date_fin' => $validated['date_fin'] ?? null,
        ]);

        return redirect()->route('particuliers.prix.show', $agent)
            ->with('success', 'Prix unitaire ajouté avec succès.');
    }

    public function updatePrix(Request $request, ParticulierAgent $agent, ParticulierAgentPrix $prix)
    {
        abort_unless($prix->particulier_agent_id === $agent->id, 404);

        $validated = $request->validate([
            'prix' => ['required', 'numeric', 'min:0'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
        ]);

        $prix->update($validated);

        return redirect()->route('particuliers.prix.show', $agent)
            ->with('success', 'Prix unitaire modifié avec succès.');
    }

    public function deletePrix(ParticulierAgent $agent, ParticulierAgentPrix $prix)
    {
        abort_unless($prix->particulier_agent_id === $agent->id, 404);

        $prix->delete();

        return redirect()->route('particuliers.prix.show', $agent)
            ->with('success', 'Prix unitaire supprimé avec succès.');
    }
}
