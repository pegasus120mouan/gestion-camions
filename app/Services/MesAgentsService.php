<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MesAgentsService
{
    public function __construct(
        private CamionsDatabaseResolver $databaseResolver,
        private ChefEquipeContext $chefContext,
    ) {}

    public function resolveToken(?Request $request = null): string
    {
        return $this->chefContext->resolveToken($request);
    }

    /**
     * @param  array{token?: string, id_chef?: int, search?: string, page?: int, per_page?: int}  $params
     * @return array{agents: list<array<string, mixed>>, pagination: array<string, int>|null, chefs: list<array<string, mixed>>, error: string|null}
     */
    public function listAgents(array $params = [], ?Request $request = null): array
    {
        $request ??= request();

        $token = trim((string) ($params['token'] ?? ''));
        $idChef = (int) ($params['id_chef'] ?? 0);
        $search = trim((string) ($params['search'] ?? ''));
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($params['per_page'] ?? 15)));

        if ($token === '' && $idChef <= 0) {
            $chefParams = $this->chefContext->apiQueryParams($request);
            $token = trim((string) ($chefParams['token'] ?? ''));
            $idChef = (int) ($chefParams['id_chef'] ?? 0);
        }

        if ($token === '' && $idChef <= 0) {
            return $this->emptyResult('Le paramètre token ou id_chef est requis.');
        }

        if ($this->databaseResolver->usesApi()) {
            return $this->fetchFromExternalApi($token, $idChef, $search, $page, $perPage);
        }

        if ($this->databaseResolver->connection() !== null) {
            return $this->fetchFromDatabase($token, $idChef, $search, $page, $perPage);
        }

        if ($this->databaseResolver->usesDatabaseOnly()) {
            return $this->emptyResult('Connexion base camions non configurée.');
        }

        return $this->fetchFromExternalApi($token, $idChef, $search, $page, $perPage);
    }

    /**
     * @return list<int>
     */
    public function chefAgentIds(?Request $request = null): array
    {
        $request ??= request();

        if ($this->cachedChefAgentIds !== null) {
            return $this->cachedChefAgentIds;
        }

        $ids = [];
        foreach ($this->fetchAllAgents([], $request) as $agent) {
            $id = (int) ($agent['id_agent'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        return $this->cachedChefAgentIds = array_values(array_unique($ids));
    }

    private ?array $cachedChefAgentIds = null;

    public function findAgentById(int $idAgent): ?array
    {
        if ($idAgent <= 0) {
            return null;
        }

        $connection = $this->databaseResolver->connection();
        if ($connection !== null) {
            try {
                $row = DB::connection($connection)->selectOne(
                    'SELECT
                        a.id_agent,
                        a.numero_agent,
                        a.nom,
                        a.prenom,
                        a.contact,
                        a.date_ajout,
                        a.id_chef,
                        ce.nom AS chef_nom,
                        ce.prenoms AS chef_prenoms,
                        ce.token AS chef_token
                    FROM agents a
                    INNER JOIN chef_equipe ce ON ce.id_chef = a.id_chef
                    WHERE a.id_agent = ?
                      AND a.date_suppression IS NULL
                    LIMIT 1',
                    [$idAgent]
                );

                return $row ? $this->normalizeAgentRow((array) $row) : null;
            } catch (\Throwable $e) {
                return null;
            }
        }

        return $this->findAgentByIdFromApi($idAgent, request());
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchAllAgents(array $params = [], ?Request $request = null): array
    {
        $all = [];
        $page = 1;

        do {
            $result = $this->listAgents(array_merge($params, ['page' => $page]), $request);
            if ($result['error']) {
                break;
            }

            $batch = $result['agents'];
            if ($batch === []) {
                break;
            }

            $all = array_merge($all, $batch);

            $pagination = $result['pagination'] ?? [];
            $lastPage = (int) ($pagination['last_page'] ?? 1);
            if ($page >= $lastPage) {
                break;
            }
            $page++;
        } while ($page <= 50);

        return $all;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listChefs(?string $token = null): array
    {
        if ($token !== null && $token !== '') {
            $chef = $this->databaseResolver->findChefByToken($token);

            return $chef ? [$chef] : [];
        }

        return $this->databaseResolver->listChefsEquipe();
    }

    private function fetchFromDatabase(
        string $token,
        int $idChef,
        string $search,
        int $page,
        int $perPage
    ): array {
        $connection = $this->databaseResolver->connection();
        if ($connection === null) {
            return $this->emptyResult('Connexion base camions non configurée.');
        }

        try {
            $bindings = [];
            $where = ['a.date_suppression IS NULL'];

            if ($token === '' && $idChef <= 0) {
                return $this->emptyResult('Le paramètre token ou id_chef est requis.');
            }

            if ($token !== '') {
                $where[] = 'ce.token = ?';
                $bindings[] = $token;
            } elseif ($idChef > 0) {
                $where[] = 'a.id_chef = ?';
                $bindings[] = $idChef;
            }

            if ($search !== '') {
                $term = '%' . $search . '%';
                $where[] = '(a.nom LIKE ? OR a.prenom LIKE ? OR a.numero_agent LIKE ? OR CONCAT(a.nom, \' \', a.prenom) LIKE ?)';
                array_push($bindings, $term, $term, $term, $term);
            }

            $whereSql = implode(' AND ', $where);

            $countRow = DB::connection($connection)->selectOne(
                "SELECT COUNT(*) AS total
                FROM agents a
                INNER JOIN chef_equipe ce ON ce.id_chef = a.id_chef
                WHERE {$whereSql}",
                $bindings
            );
            $total = (int) ($countRow->total ?? 0);
            $lastPage = max(1, (int) ceil($total / $perPage));
            $page = min($page, $lastPage);
            $offset = ($page - 1) * $perPage;

            $rows = DB::connection($connection)->select(
                "SELECT
                    a.id_agent,
                    a.numero_agent,
                    a.nom,
                    a.prenom,
                    a.contact,
                    a.date_ajout,
                    a.id_chef,
                    ce.nom AS chef_nom,
                    ce.prenoms AS chef_prenoms,
                    ce.token AS chef_token
                FROM agents a
                INNER JOIN chef_equipe ce ON ce.id_chef = a.id_chef
                WHERE {$whereSql}
                ORDER BY a.date_ajout DESC, a.id_agent DESC
                LIMIT {$perPage} OFFSET {$offset}",
                $bindings
            );

            $agents = array_map(
                fn ($row) => $this->normalizeAgentRow((array) $row),
                $rows
            );

            $chefs = $this->listChefs($token !== '' ? $token : null);
            if ($chefs === [] && $agents !== []) {
                $chefsMap = [];
                foreach ($agents as $agent) {
                    $chef = $agent['chef_equipe'] ?? null;
                    if (is_array($chef) && !empty($chef['id_chef'])) {
                        $chefsMap[$chef['id_chef']] = $chef;
                    }
                }
                $chefs = array_values($chefsMap);
            }

            return [
                'agents' => $agents,
                'pagination' => [
                    'current_page' => $page,
                    'last_page' => $lastPage,
                    'per_page' => $perPage,
                    'total' => $total,
                ],
                'chefs' => $chefs,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return $this->emptyResult('Erreur lors de la lecture des agents : ' . $e->getMessage());
        }
    }

    /**
     * @return array{agents: list<array<string, mixed>>, pagination: array<string, int>|null, chefs: list<array<string, mixed>>, error: string|null}
     */
    private function fetchFromExternalApi(
        string $token,
        int $idChef,
        string $search,
        int $page,
        int $perPage
    ): array {
        $url = (string) config('services.external_auth.mes_agents_url', '');
        if ($url === '') {
            return $this->emptyResult('URL API mes_agents non configurée.');
        }

        if ($token === '' && $idChef <= 0) {
            return $this->emptyResult('Le paramètre token ou id_chef est requis.');
        }

        $queryParams = [
            'page' => $page,
            'per_page' => $perPage,
        ];

        if ($search !== '') {
            $queryParams['search'] = $search;
        }
        if ($token !== '') {
            $queryParams['token'] = $token;
        } elseif ($idChef > 0) {
            $queryParams['id_chef'] = $idChef;
        }

        try {
            $response = Http::acceptJson()
                ->withoutVerifying()
                ->timeout((int) config('services.external_auth.timeout', 10))
                ->get($url, $queryParams);
        } catch (\Throwable $e) {
            return $this->emptyResult('Impossible de joindre le service agents.');
        }

        if (!$response->successful()) {
            return $this->emptyResult((string) ($response->json('error') ?? 'Erreur API agents.'));
        }

        $agents = $response->json('agents');
        if (!is_array($agents)) {
            $agents = [];
        }

        $chefs = $response->json('chefs');
        if (!is_array($chefs)) {
            $chefs = [];
            foreach ($agents as $agent) {
                if (!empty($agent['chef_equipe']['id_chef'])) {
                    $chefId = (int) $agent['chef_equipe']['id_chef'];
                    $chefs[$chefId] = $agent['chef_equipe'];
                }
            }
            $chefs = array_values($chefs);
        }

        return [
            'agents' => $agents,
            'pagination' => is_array($response->json('pagination')) ? $response->json('pagination') : null,
            'chefs' => $chefs,
            'error' => null,
        ];
    }

    private function findAgentByIdFromApi(int $idAgent, ?Request $request = null): ?array
    {
        $request ??= request();
        $url = (string) config('services.external_auth.mes_agents_url', '');
        if ($url === '') {
            return null;
        }

        $chefParams = $this->chefContext->apiQueryParams($request);
        $timeout = (int) config('services.external_auth.timeout', 10);
        $page = 1;

        try {
            while ($page <= 50) {
                $response = Http::acceptJson()
                    ->withoutVerifying()
                    ->timeout($timeout)
                    ->get($url, array_merge($chefParams, ['page' => $page]));

                if (!$response->successful()) {
                    break;
                }

                $agents = $response->json('agents') ?? [];
                foreach ($agents as $agent) {
                    if ((int) ($agent['id_agent'] ?? 0) === $idAgent) {
                        return $agent;
                    }
                }

                $pagination = $response->json('pagination') ?? [];
                if ($page >= (int) ($pagination['last_page'] ?? 1)) {
                    break;
                }
                $page++;
            }
        } catch (\Throwable $e) {
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeAgentRow(array $row): array
    {
        $nom = trim((string) ($row['nom'] ?? ''));
        $prenom = trim((string) ($row['prenom'] ?? $row['prenoms'] ?? ''));

        return [
            'id_agent' => (int) ($row['id_agent'] ?? 0),
            'numero_agent' => (string) ($row['numero_agent'] ?? ''),
            'nom' => $nom,
            'prenom' => $prenom,
            'nom_complet' => trim($nom . ' ' . $prenom),
            'contact' => (string) ($row['contact'] ?? ''),
            'date_ajout' => $row['date_ajout'] ?? null,
            'id_chef' => (int) ($row['id_chef'] ?? 0),
            'chef_equipe' => $this->normalizeChefRow([
                'id_chef' => $row['id_chef'] ?? 0,
                'nom' => $row['chef_nom'] ?? '',
                'prenoms' => $row['chef_prenoms'] ?? '',
                'token' => $row['chef_token'] ?? '',
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function normalizeChefRow(array $row): array
    {
        $nom = trim((string) ($row['nom'] ?? ''));
        $prenoms = trim((string) ($row['prenoms'] ?? ''));

        return [
            'id_chef' => (int) ($row['id_chef'] ?? 0),
            'nom' => $nom,
            'prenoms' => $prenoms,
            'nom_complet' => trim($nom . ' ' . $prenoms),
            'token' => (string) ($row['token'] ?? ''),
        ];
    }

    /**
     * @return array{agents: list<array<string, mixed>>, pagination: null, chefs: list<array<string, mixed>>, error: string}
     */
    private function emptyResult(string $error): array
    {
        return [
            'agents' => [],
            'pagination' => null,
            'chefs' => [],
            'error' => $error,
        ];
    }
}
