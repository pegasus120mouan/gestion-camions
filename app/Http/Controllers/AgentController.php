<?php

namespace App\Http\Controllers;

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
}
