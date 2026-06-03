<?php

namespace App\Http\Controllers;

use App\Models\ParticulierAgent;
use App\Models\ParticulierGroupe;
use Illuminate\Http\Request;

class ParticulierController extends Controller
{
    private function prochainNumeroAgent(): string
    {
        $lastNumero = ParticulierAgent::query()
            ->where('numero_agent', 'like', 'AGP-%')
            ->orderByDesc('id')
            ->value('numero_agent');

        if ($lastNumero && preg_match('/^AGP-(\d+)$/', $lastNumero, $matches)) {
            $next = (int) $matches[1] + 1;
        } else {
            $next = ParticulierAgent::count() + 1;
        }

        return 'AGP-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function index()
    {
        $groupes = ParticulierGroupe::withCount('agents')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('particuliers.index', [
            'groupes' => $groupes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom_groupe' => ['required', 'string', 'max:255'],
        ]);

        ParticulierGroupe::create($validated);

        return redirect()->route('particuliers.index')
            ->with('success', 'Groupe particulier créé avec succès.');
    }

    public function agentsIndex(Request $request)
    {
        $query = ParticulierAgent::with('groupe')
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

        return view('particuliers.agents.index', [
            'agents' => $query->paginate(20)->withQueryString(),
            'groupes' => ParticulierGroupe::orderBy('nom_groupe')->get(),
            'prochainNumero' => $this->prochainNumeroAgent(),
        ]);
    }

    public function storeAgent(Request $request)
    {
        $validated = $request->validate([
            'particulier_groupe_id' => ['required', 'exists:particulier_groupes,id'],
            'numero_agent' => ['required', 'string', 'max:50', 'unique:particulier_agents,numero_agent'],
            'nom' => ['required', 'string', 'max:100'],
            'prenoms' => ['required', 'string', 'max:150'],
            'contact' => ['nullable', 'string', 'max:50'],
        ]);

        ParticulierAgent::create($validated);

        $redirect = $request->input('redirect');
        if ($redirect === 'show') {
            return redirect()->route('particuliers.show', $validated['particulier_groupe_id'])
                ->with('success', 'Agent ajouté avec succès.');
        }

        return redirect()->route('particuliers.agents.index')
            ->with('success', 'Agent créé avec succès.');
    }

    public function updateAgent(Request $request, ParticulierAgent $agent)
    {
        $validated = $request->validate([
            'particulier_groupe_id' => ['required', 'exists:particulier_groupes,id'],
            'numero_agent' => ['required', 'string', 'max:50', 'unique:particulier_agents,numero_agent,' . $agent->id],
            'nom' => ['required', 'string', 'max:100'],
            'prenoms' => ['required', 'string', 'max:150'],
            'contact' => ['nullable', 'string', 'max:50'],
        ]);

        $agent->update($validated);

        return redirect()->route('particuliers.agents.index')
            ->with('success', 'Agent modifié avec succès.');
    }

    public function destroyAgent(ParticulierAgent $agent)
    {
        $agent->delete();

        return redirect()->back()->with('success', 'Agent supprimé avec succès.');
    }

    public function show(int $id)
    {
        $groupe = ParticulierGroupe::with('agents')->findOrFail($id);

        return view('particuliers.show', [
            'groupe' => $groupe,
            'prochainNumero' => $this->prochainNumeroAgent(),
        ]);
    }

    public function destroy(int $id)
    {
        $groupe = ParticulierGroupe::findOrFail($id);
        $groupe->delete();

        return redirect()->route('particuliers.index')
            ->with('success', 'Groupe particulier supprimé avec succès.');
    }
}
