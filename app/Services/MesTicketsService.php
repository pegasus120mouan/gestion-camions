<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MesTicketsService
{
    public function __construct(
        private CamionsDatabaseResolver $databaseResolver,
        private ChefEquipeContext $chefContext,
    ) {}

    /**
     * @param  array{token?: string, id_chef?: int, page?: int, per_page?: int}  $params
     * @return array{tickets: list<array<string, mixed>>, pagination: array<string, int>|null, chef: array<string, mixed>|null, error: string|null}
     */
    public function listTickets(array $params = [], ?Request $request = null): array
    {
        $request ??= request();

        $token = trim((string) ($params['token'] ?? ''));
        $idChef = (int) ($params['id_chef'] ?? 0);
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($params['per_page'] ?? 20)));

        if ($token === '' && $idChef <= 0) {
            $chefParams = $this->chefContext->apiQueryParams($request);
            $token = trim((string) ($chefParams['token'] ?? ''));
            $idChef = (int) ($chefParams['id_chef'] ?? 0);
        }

        if ($token === '' && $idChef <= 0) {
            return $this->emptyResult('Le paramètre token ou id_chef est requis.');
        }

        if ($this->databaseResolver->usesApi()) {
            return $this->fetchFromExternalApi($token, $idChef, $page, $perPage);
        }

        if ($this->databaseResolver->connection() !== null) {
            return $this->fetchFromDatabase($token, $idChef, $page, $perPage);
        }

        return $this->fetchFromExternalApi($token, $idChef, $page, $perPage);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function fetchAllTickets(array $params = [], ?Request $request = null): array
    {
        $all = [];
        $page = 1;

        do {
            $result = $this->listTickets(array_merge($params, ['page' => $page]), $request);
            if ($result['error']) {
                break;
            }

            $batch = $result['tickets'];
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
        } while ($page <= 100);

        return $all;
    }

  /**
     * @return array<string, mixed>|null
     */
    public function findTicketById(int $idTicket, ?Request $request = null): ?array
    {
        if ($idTicket <= 0) {
            return null;
        }

        foreach ($this->fetchAllTickets([], $request) as $ticket) {
            if ((int) ($ticket['id_ticket'] ?? 0) === $idTicket) {
                return $ticket;
            }
        }

        return null;
    }

    /**
     * @return array{tickets: list<array<string, mixed>>, pagination: array<string, int>|null, chef: array<string, mixed>|null, error: string|null}
     */
    private function fetchFromExternalApi(string $token, int $idChef, int $page, int $perPage): array
    {
        $url = (string) config('services.external_auth.mes_tickets_url', '');
        if ($url === '') {
            return $this->emptyResult('URL API mes_tickets non configurée.');
        }

        $query = ['page' => $page, 'per_page' => $perPage];
        if ($token !== '') {
            $query['token'] = $token;
        } else {
            $query['id_chef'] = $idChef;
        }

        try {
            $response = Http::acceptJson()
                ->withoutVerifying()
                ->timeout((int) config('services.external_auth.timeout', 10))
                ->get($url, $query);
        } catch (\Throwable $e) {
            return $this->emptyResult('Impossible de joindre le service tickets.');
        }

        if (!$response->successful()) {
            return $this->emptyResult((string) ($response->json('error') ?? 'Erreur API tickets.'));
        }

        $tickets = $response->json('tickets');
        if (!is_array($tickets)) {
            $tickets = [];
        }

        return [
            'tickets' => array_map([$this, 'normalizeTicketRow'], $tickets),
            'pagination' => is_array($response->json('pagination')) ? $response->json('pagination') : null,
            'chef' => is_array($response->json('chef')) ? $response->json('chef') : null,
            'error' => null,
        ];
    }

    /**
     * @return array{tickets: list<array<string, mixed>>, pagination: array<string, int>|null, chef: array<string, mixed>|null, error: string|null}
     */
    private function fetchFromDatabase(string $token, int $idChef, int $page, int $perPage): array
    {
        $connection = $this->databaseResolver->connection();
        if ($connection === null) {
            return $this->emptyResult('Connexion base camions non configurée.');
        }

        try {
            $bindings = [];
            $where = ['a.date_suppression IS NULL'];

            if ($token !== '') {
                $where[] = 'ce.token = ?';
                $bindings[] = $token;
            } else {
                $where[] = 'a.id_chef = ?';
                $bindings[] = $idChef;
            }

            $whereSql = implode(' AND ', $where);

            $countRow = DB::connection($connection)->selectOne(
                "SELECT COUNT(*) AS total
                FROM tickets t
                INNER JOIN agents a ON a.id_agent = t.id_agent
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
                    t.id_ticket,
                    t.id_usine,
                    t.date_ticket,
                    t.id_agent,
                    t.numero_ticket,
                    t.vehicule_id,
                    t.poids,
                    t.prix_unitaire,
                    t.montant_paie,
                    t.montant_payer,
                    t.montant_reste,
                    t.statut_ticket,
                    t.created_at,
                    v.matricule_vehicule,
                    a.nom AS agent_nom,
                    a.prenom AS agent_prenom,
                    u.nom_usine
                FROM tickets t
                INNER JOIN agents a ON a.id_agent = t.id_agent
                INNER JOIN chef_equipe ce ON ce.id_chef = a.id_chef
                LEFT JOIN vehicules v ON v.vehicules_id = t.vehicule_id
                LEFT JOIN usines u ON u.id_usine = t.id_usine
                WHERE {$whereSql}
                ORDER BY t.id_ticket DESC
                LIMIT {$perPage} OFFSET {$offset}",
                $bindings
            );

            $chef = null;
            if ($token !== '') {
                $chefRow = DB::connection($connection)->selectOne(
                    'SELECT id_chef, nom, prenoms, token FROM chef_equipe WHERE token = ? LIMIT 1',
                    [$token]
                );
                if ($chefRow) {
                    $chef = $this->normalizeChefRow((array) $chefRow);
                }
            } elseif ($idChef > 0) {
                $chefRow = DB::connection($connection)->selectOne(
                    'SELECT id_chef, nom, prenoms, token FROM chef_equipe WHERE id_chef = ? LIMIT 1',
                    [$idChef]
                );
                if ($chefRow) {
                    $chef = $this->normalizeChefRow((array) $chefRow);
                }
            }

            return [
                'tickets' => array_map(fn ($row) => $this->normalizeTicketRow((array) $row), $rows),
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => $lastPage,
                ],
                'chef' => $chef,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return $this->emptyResult('Erreur lors de la lecture des tickets : ' . $e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function normalizeTicketRow(array $row): array
    {
        $agentNom = trim((string) ($row['agent_nom'] ?? ''));
        $agentPrenom = trim((string) ($row['agent_prenom'] ?? ''));
        $nomAgent = trim($agentNom . ' ' . $agentPrenom);

        return [
            'id_ticket' => (int) ($row['id_ticket'] ?? 0),
            'numero_ticket' => (string) ($row['numero_ticket'] ?? ''),
            'date_ticket' => $row['date_ticket'] ?? null,
            'matricule_vehicule' => (string) ($row['matricule_vehicule'] ?? ''),
            'vehicule_id' => (int) ($row['vehicule_id'] ?? 0),
            'poids' => (float) ($row['poids'] ?? 0),
            'id_usine' => (int) ($row['id_usine'] ?? 0),
            'nom_usine' => (string) ($row['nom_usine'] ?? ''),
            'id_agent' => (int) ($row['id_agent'] ?? 0),
            'nom_agent' => $nomAgent !== '' ? $nomAgent : '-',
            'prix_unitaire' => $row['prix_unitaire'] ?? null,
            'montant_paie' => $row['montant_paie'] ?? null,
            'statut_ticket' => $row['statut_ticket'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'conformite' => null,
            'nom_groupe' => '-',
            'particulier_agent_id' => null,
            'prix_unitaire_agent' => null,
            'montant_calcule' => null,
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
     * @param  list<array<string, mixed>>  $tickets
     * @return list<array<string, mixed>>
     */
    public function filterTickets(array $tickets, string $vehicule, string $usine, string $agent): array
    {
        return array_values(array_filter($tickets, function (array $t) use ($vehicule, $usine, $agent) {
            if ($vehicule !== '') {
                $matricule = mb_strtolower((string) ($t['matricule_vehicule'] ?? ''), 'UTF-8');
                if (!str_contains($matricule, mb_strtolower($vehicule, 'UTF-8'))) {
                    return false;
                }
            }

            if ($usine !== '') {
                $nomUsine = mb_strtolower((string) ($t['nom_usine'] ?? ''), 'UTF-8');
                $idUsine = (string) ($t['id_usine'] ?? '');
                $search = mb_strtolower($usine, 'UTF-8');
                if ($idUsine !== $usine && !str_contains($nomUsine, $search)) {
                    return false;
                }
            }

            if ($agent !== '') {
                $nomAgent = mb_strtolower((string) ($t['nom_agent'] ?? ''), 'UTF-8');
                $search = mb_strtolower($agent, 'UTF-8');
                if (ctype_digit($agent)) {
                    if ((int) ($t['id_agent'] ?? 0) !== (int) $agent) {
                        return false;
                    }
                } elseif (!str_contains($nomAgent, $search)) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * @return array{tickets: list<array<string, mixed>>, pagination: null, chef: null, error: string}
     */
    private function emptyResult(string $error): array
    {
        return [
            'tickets' => [],
            'pagination' => null,
            'chef' => null,
            'error' => $error,
        ];
    }
}
