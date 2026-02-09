<?php

namespace App\Http\Controllers;

use App\Models\PrixAgent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        $mesAgentsUrl = (string) config('services.external_auth.mes_agents_url');
        $timeout = (int) config('services.external_auth.timeout', 10);

        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('search', ''));
        $idChef = (int) $request->query('id_chef', 0);

        $queryParams = ['page' => $page];
        if ($search !== '') {
            $queryParams['search'] = $search;
        }
        if ($idChef > 0) {
            $queryParams['id_chef'] = $idChef;
        }

        try {
            $response = Http::acceptJson()
                ->timeout($timeout)
                ->get($mesAgentsUrl, $queryParams);
        } catch (\Throwable $e) {
            return view('agents.index', [
                'agents' => [],
                'chefs' => [],
                'pagination' => null,
                'external_error' => "Impossible de joindre le service agents.",
            ]);
        }

        if (!$response->successful()) {
            $message = (string) ($response->json('error') ?? 'Erreur API.');

            return view('agents.index', [
                'agents' => [],
                'chefs' => [],
                'pagination' => null,
                'external_error' => $message,
            ]);
        }

        $agents = $response->json('agents');
        if (!is_array($agents)) {
            $agents = [];
        }

        $pagination = $response->json('pagination');

        // Extraire la liste unique des chefs pour le filtre
        $chefs = [];
        foreach ($agents as $agent) {
            if (!empty($agent['chef_equipe'])) {
                $chefId = $agent['chef_equipe']['id_chef'];
                if (!isset($chefs[$chefId])) {
                    $chefs[$chefId] = $agent['chef_equipe'];
                }
            }
        }
        $chefs = array_values($chefs);

        return view('agents.index', [
            'agents' => $agents,
            'chefs' => $chefs,
            'pagination' => $pagination,
            'external_error' => null,
        ]);
    }

    public function show(Request $request, int $id_agent)
    {
        $mesAgentsUrl = (string) config('services.external_auth.mes_agents_url');
        $timeout = (int) config('services.external_auth.timeout', 10);

        // Récupérer l'agent depuis l'API avec l'ID spécifique
        $agent = null;
        try {
            // Parcourir toutes les pages pour trouver l'agent
            $page = 1;
            $maxPages = 50; // Limite de sécurité
            
            while ($page <= $maxPages) {
                $response = Http::acceptJson()
                    ->timeout($timeout)
                    ->get($mesAgentsUrl, ['page' => $page]);
                
                if (!$response->successful()) {
                    break;
                }
                
                $agents = $response->json('agents') ?? [];
                $pagination = $response->json('pagination') ?? [];
                
                foreach ($agents as $a) {
                    if (($a['id_agent'] ?? 0) == $id_agent) {
                        $agent = $a;
                        break 2; // Sortir des deux boucles
                    }
                }
                
                // Vérifier s'il y a d'autres pages
                $lastPage = (int) ($pagination['last_page'] ?? 1);
                if ($page >= $lastPage) {
                    break;
                }
                $page++;
            }
        } catch (\Throwable $e) {
            return redirect()->route('agents.index')->withErrors(['error' => 'Impossible de joindre le service agents.']);
        }

        if (!$agent) {
            return redirect()->route('agents.index')->withErrors(['error' => 'Agent non trouvé.']);
        }

        // Récupérer la liste des usines
        $mesUsinesUrl = (string) config('services.external_auth.mes_usines_url');
        $usines = [];
        try {
            $usinesResponse = Http::acceptJson()
                ->timeout($timeout)
                ->get($mesUsinesUrl);
            if ($usinesResponse->successful()) {
                $usines = $usinesResponse->json('usines') ?? [];
            }
        } catch (\Throwable $e) {}

        // Récupérer les prix de l'agent
        $prixTransporteur = PrixAgent::where('id_agent', $id_agent)
            ->where('type', 'transporteur')
            ->orderBy('nom_usine')
            ->get();
        
        $prixPgf = PrixAgent::where('id_agent', $id_agent)
            ->where('type', 'pgf')
            ->orderBy('nom_usine')
            ->get();

        return view('agents.show', [
            'agent' => $agent,
            'usines' => $usines,
            'prixTransporteur' => $prixTransporteur,
            'prixPgf' => $prixPgf,
        ]);
    }

    public function storePrix(Request $request, int $id_agent)
    {
        $validated = $request->validate([
            'id_usine' => ['required', 'integer'],
            'nom_usine' => ['required', 'string'],
            'type' => ['required', 'in:transporteur,pgf'],
            'prix' => ['required', 'numeric', 'min:0'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
        ]);

        PrixAgent::create([
            'id_agent' => $id_agent,
            'id_usine' => $validated['id_usine'],
            'nom_usine' => $validated['nom_usine'],
            'type' => $validated['type'],
            'prix' => $validated['prix'],
            'date_debut' => $validated['date_debut'],
            'date_fin' => $validated['date_fin'],
        ]);

        return redirect()->route('agents.show', ['id_agent' => $id_agent])
            ->with('success', 'Prix ajouté avec succès.');
    }

    public function updatePrix(Request $request, int $id_agent, int $prix_id)
    {
        $validated = $request->validate([
            'prix' => ['required', 'numeric', 'min:0'],
            'date_debut' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_debut'],
        ]);

        $prix = PrixAgent::where('id', $prix_id)->where('id_agent', $id_agent)->first();
        
        if ($prix) {
            $prix->update($validated);
            return redirect()->route('agents.show', ['id_agent' => $id_agent])
                ->with('success', 'Prix modifié avec succès.');
        }

        return redirect()->route('agents.show', ['id_agent' => $id_agent])
            ->withErrors(['error' => 'Prix non trouvé.']);
    }

    public function deletePrix(int $id_agent, int $prix_id)
    {
        $prix = PrixAgent::where('id', $prix_id)->where('id_agent', $id_agent)->first();
        
        if ($prix) {
            $prix->delete();
            return redirect()->route('agents.show', ['id_agent' => $id_agent])
                ->with('success', 'Prix supprimé avec succès.');
        }

        return redirect()->route('agents.show', ['id_agent' => $id_agent])
            ->withErrors(['error' => 'Prix non trouvé.']);
    }
}
