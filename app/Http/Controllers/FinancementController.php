<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FinancementController extends Controller
{
    public function index(Request $request)
    {
        $mesFinancementsUrl = (string) config('services.external_auth.mes_financements_url');
        $timeout = (int) config('services.external_auth.timeout', 10);
        $phpsessid = session('external_auth.phpsessid', '');

        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('search', ''));

        $queryParams = ['page' => $page];
        if ($search !== '') {
            $queryParams['search'] = $search;
        }

        try {
            $response = Http::acceptJson()
                ->timeout($timeout)
                ->withHeaders(['Cookie' => 'PHPSESSID=' . $phpsessid])
                ->get($mesFinancementsUrl, $queryParams);
        } catch (\Throwable $e) {
            return view('financements.index', [
                'financements' => [],
                'pagination' => null,
                'external_error' => "Impossible de joindre le service financements.",
            ]);
        }

        if (!$response->successful()) {
            $message = (string) ($response->json('error') ?? 'Erreur API.');

            return view('financements.index', [
                'financements' => [],
                'pagination' => null,
                'external_error' => $message,
            ]);
        }

        $financementsRaw = $response->json('financements');
        if (!is_array($financementsRaw)) {
            $financementsRaw = [];
        }

        $pagination = $response->json('pagination');

        // Grouper les financements par agent
        $grouped = [];
        foreach ($financementsRaw as $f) {
            $idAgent = $f['id_agent'] ?? 0;
            $nomAgent = $f['nom_agent'] ?? '-';
            $numeroAgent = $f['numero_agent'] ?? '-';
            $montant = (float) ($f['montant'] ?? 0);

            if (!isset($grouped[$idAgent])) {
                $grouped[$idAgent] = [
                    'id_agent' => $idAgent,
                    'nom_agent' => $nomAgent,
                    'numero_agent' => $numeroAgent,
                    'nombre_financements' => 0,
                    'montant_initial' => 0,
                    'deja_rembourse' => 0,
                    'solde_financement' => 0,
                ];
            }
            $grouped[$idAgent]['nombre_financements']++;
            $grouped[$idAgent]['montant_initial'] += $montant;
            $grouped[$idAgent]['solde_financement'] += $montant;
        }

        // Trier par montant initial décroissant
        usort($grouped, function($a, $b) {
            return $b['montant_initial'] <=> $a['montant_initial'];
        });

        $financements = array_values($grouped);

        return view('financements.index', [
            'financements' => $financements,
            'pagination' => $pagination,
            'external_error' => null,
        ]);
    }
}
