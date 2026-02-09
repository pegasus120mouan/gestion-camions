<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class UsineController extends Controller
{
    public function index(Request $request)
    {
        $mesUsinesUrl = (string) config('services.external_auth.mes_usines_url');
        $timeout = (int) config('services.external_auth.timeout', 10);

        $page = max(1, (int) $request->query('page', 1));
        $search = trim((string) $request->query('search', ''));

        $queryParams = ['page' => $page];
        if ($search !== '') {
            $queryParams['search'] = $search;
        }

        try {
            $response = Http::acceptJson()
                ->timeout($timeout)
                ->get($mesUsinesUrl, $queryParams);
        } catch (\Throwable $e) {
            return view('usines.index', [
                'usines' => [],
                'pagination' => null,
                'external_error' => "Impossible de joindre le service usines.",
            ]);
        }

        if (!$response->successful()) {
            $message = (string) ($response->json('error') ?? 'Erreur API.');

            return view('usines.index', [
                'usines' => [],
                'pagination' => null,
                'external_error' => $message,
            ]);
        }

        $usines = $response->json('usines');
        if (!is_array($usines)) {
            $usines = [];
        }

        $pagination = $response->json('pagination');

        return view('usines.index', [
            'usines' => $usines,
            'pagination' => $pagination,
            'external_error' => null,
        ]);
    }
}
