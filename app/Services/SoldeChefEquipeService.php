<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SoldeChefEquipeService
{
    public function __construct(
        private CamionsDatabaseResolver $databaseResolver,
    ) {}

    public function getSoldeByToken(string $token): ?array
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }

        if ($this->databaseResolver->usesApi()) {
            return $this->fetchFromExternalApi($token);
        }

        if ($this->databaseResolver->connection() !== null) {
            $fromDb = $this->fetchFromDatabase($token);
            if ($fromDb !== null) {
                return $fromDb;
            }
        }

        if ($this->databaseResolver->usesDatabaseOnly()) {
            return null;
        }

        return $this->fetchFromExternalApi($token);
    }

    public function getSoldeForContext(ChefEquipeContext $context, ?\Illuminate\Http\Request $request = null): ?array
    {
        $token = $context->resolveToken($request);
        if ($token === '') {
            return null;
        }

        return $this->getSoldeByToken($token);
    }

    private function fetchFromExternalApi(string $token): ?array
    {
        $url = (string) config('services.external_auth.solde_chef_equipe_url', '');
        if ($url === '') {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->withoutVerifying()
                ->timeout((int) config('services.external_auth.timeout', 10))
                ->get($url, ['token' => $token]);

            if (!$response->successful()) {
                return null;
            }

            $solde = $response->json('solde');
            if (!is_array($solde)) {
                return null;
            }

            return $this->normalizeSolde($solde);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function fetchFromDatabase(string $token): ?array
    {
        $connection = $this->databaseResolver->connection();
        if ($connection === null) {
            return null;
        }

        try {
            $row = DB::connection($connection)->selectOne(
                'SELECT 
                    ce.id_chef,
                    ce.nom,
                    ce.prenoms,
                    ce.token,
                    COALESCE(SUM(t.montant_paie), 0) AS total_montant,
                    COALESCE(SUM(t.montant_payer), 0) AS montant_paye,
                    COALESCE(SUM(t.montant_paie), 0) - COALESCE(SUM(t.montant_payer), 0) AS reste_a_payer
                FROM chef_equipe ce
                LEFT JOIN agents a ON a.id_chef = ce.id_chef AND a.date_suppression IS NULL
                LEFT JOIN tickets t ON t.id_agent = a.id_agent AND t.montant_paie IS NOT NULL
                WHERE ce.token = ?
                GROUP BY ce.id_chef, ce.nom, ce.prenoms, ce.token',
                [$token]
            );

            if (!$row) {
                return null;
            }

            return $this->normalizeSolde((array) $row);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeSolde(array $solde): array
    {
        return [
            'id_chef' => (int) ($solde['id_chef'] ?? 0),
            'nom' => (string) ($solde['nom'] ?? ''),
            'prenoms' => (string) ($solde['prenoms'] ?? ''),
            'token' => (string) ($solde['token'] ?? ''),
            'total_montant' => (float) ($solde['total_montant'] ?? 0),
            'montant_paye' => (float) ($solde['montant_paye'] ?? 0),
            'reste_a_payer' => (float) ($solde['reste_a_payer'] ?? 0),
        ];
    }
}
