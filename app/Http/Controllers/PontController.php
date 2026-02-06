<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PontController extends Controller
{
    public function index(Request $request)
    {
        $mesPontsUrl = (string) config('services.external_auth.mes_ponts_url');
        $timeout = (int) config('services.external_auth.timeout', 10);

        try {
            $response = Http::acceptJson()
                ->timeout($timeout)
                ->get($mesPontsUrl);
        } catch (\Throwable $e) {
            return view('ponts.index', [
                'ponts' => [],
                'external_error' => "Impossible de joindre le service ponts.",
            ]);
        }

        if (!$response->successful()) {
            $message = (string) ($response->json('error') ?? 'Erreur API.');

            return view('ponts.index', [
                'ponts' => [],
                'external_error' => $message,
            ]);
        }

        $ponts = $response->json('ponts');
        if (!is_array($ponts)) {
            $ponts = [];
        }

        return view('ponts.index', [
            'ponts' => $ponts,
            'external_error' => null,
        ]);
    }
}
