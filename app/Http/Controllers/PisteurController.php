<?php

namespace App\Http\Controllers;

use App\Models\Pisteur;
use App\Models\PisteurPrix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PisteurController extends Controller
{
    public function index()
    {
        $pisteurs = Pisteur::orderBy('nom')->paginate(20);
        
        // Récupérer les agents depuis l'API
        $agents = $this->getAgentsFromApi();
        
        return view('pisteurs.index', compact('pisteurs', 'agents'));
    }

    private function getAgentsFromApi(): array
    {
        $timeout = (int) config('services.external_auth.timeout', 10);
        $agents = [];
        $page = 1;
        $maxPages = 10;

        try {
            while ($page <= $maxPages) {
                $response = Http::acceptJson()
                    ->withoutVerifying()
                    ->timeout($timeout)
                    ->get('https://api.objetombrepegasus.online/api/camions/mes_agents.php', ['page' => $page]);
                
                if ($response->successful()) {
                    $pageAgents = $response->json('agents') ?? [];
                    if (empty($pageAgents)) {
                        break;
                    }
                    $agents = array_merge($agents, $pageAgents);
                    $page++;
                } else {
                    break;
                }
            }
        } catch (\Throwable $e) {}

        return $agents;
    }

    public function store(Request $request)
    {
        // Si un agent est sélectionné depuis l'API
        if ($request->filled('id_agent')) {
            $agents = $this->getAgentsFromApi();
            $agentSelectionne = collect($agents)->firstWhere('id_agent', $request->id_agent);
            
            if ($agentSelectionne) {
                // Vérifier si ce pisteur existe déjà (par id_agent)
                $existant = Pisteur::where('id_agent', $request->id_agent)->first();
                if ($existant) {
                    return redirect()->route('pisteurs.index')->with('error', 'Ce pisteur existe déjà.');
                }

                // Extraire nom et prénoms depuis nom_complet
                $nomComplet = $agentSelectionne['nom_complet'] ?? '';
                $parts = explode(' ', $nomComplet, 2);
                $nom = $parts[0] ?? '';
                $prenoms = $parts[1] ?? '';

                Pisteur::create([
                    'id_agent' => $agentSelectionne['id_agent'] ?? null,
                    'nom' => $nom,
                    'prenoms' => $prenoms,
                    'contact' => $agentSelectionne['numero_agent'] ?? $request->contact,
                ]);

                return redirect()->route('pisteurs.index')->with('success', 'Pisteur ajouté depuis la liste des agents.');
            }
        }

        // Création manuelle
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'prenoms' => ['required', 'string', 'max:150'],
            'contact' => ['nullable', 'string', 'max:50'],
        ]);

        Pisteur::create($validated);

        return redirect()->route('pisteurs.index')->with('success', 'Pisteur créé avec succès.');
    }

    public function update(Request $request, Pisteur $pisteur)
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'prenoms' => ['required', 'string', 'max:150'],
            'contact' => ['nullable', 'string', 'max:50'],
            'prix_unitaire' => ['nullable', 'integer', 'min:0'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date'],
        ]);

        $pisteur->update($validated);

        return redirect()->route('pisteurs.index')->with('success', 'Pisteur modifié avec succès.');
    }

    public function destroy(Pisteur $pisteur)
    {
        $pisteur->delete();
        return redirect()->route('pisteurs.index')->with('success', 'Pisteur supprimé avec succès.');
    }

    public function show(Pisteur $pisteur)
    {
        $pisteur->load(['prixPeriodes']);

        return view('pisteurs.show', [
            'pisteur' => $pisteur,
        ]);
    }

    public function storePrix(Request $request, Pisteur $pisteur)
    {
        $validated = $request->validate([
            'prix_unitaire' => ['required', 'integer', 'min:0'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date'],
        ]);

        $dateDebut = $validated['date_debut'];
        $dateFin = $validated['date_fin'] ?? null;

        $chevauchement = $pisteur->prixPeriodes()
            ->where(function ($query) use ($dateDebut, $dateFin) {
                $query->where(function ($q) use ($dateDebut, $dateFin) {
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

        if ($chevauchement) {
            return redirect()->route('pisteurs.show', $pisteur)
                ->with('error', 'Cette période chevauche une période existante. Veuillez choisir des dates différentes.');
        }

        $pisteur->prixPeriodes()->create($validated);

        return redirect()->route('pisteurs.show', $pisteur)->with('success', 'Prix ajouté avec succès.');
    }

    public function updatePrix(Request $request, Pisteur $pisteur, PisteurPrix $prix)
    {
        $validated = $request->validate([
            'prix_unitaire' => ['required', 'integer', 'min:0'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date'],
        ]);

        $dateDebut = $validated['date_debut'];
        $dateFin = $validated['date_fin'] ?? null;

        $chevauchement = $pisteur->prixPeriodes()
            ->where('id', '!=', $prix->id)
            ->where(function ($query) use ($dateDebut, $dateFin) {
                $query->where(function ($q) use ($dateDebut, $dateFin) {
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

        if ($chevauchement) {
            return redirect()->route('pisteurs.show', $pisteur)
                ->with('error', 'Cette période chevauche une période existante. Veuillez choisir des dates différentes.');
        }

        $prix->update($validated);

        return redirect()->route('pisteurs.show', $pisteur)->with('success', 'Prix modifié avec succès.');
    }

    public function destroyPrix(Pisteur $pisteur, PisteurPrix $prix)
    {
        $prix->delete();

        return redirect()->route('pisteurs.show', $pisteur)->with('success', 'Prix supprimé avec succès.');
    }
}
