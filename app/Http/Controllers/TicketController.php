<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $authUser = Auth::user();

        if (!$authUser || $authUser->role !== 'proprietaire') {
            return view('tickets.index', [
                'tickets' => [],
                'pagination' => null,
                'external_error' => "Accès réservé aux propriétaires.",
            ]);
        }

        $mesTicketsUrl = (string) config('services.external_auth.mes_tickets_url');
        $mesCamionsUrl = (string) config('services.external_auth.mes_camions_url');
        $timeout = (int) config('services.external_auth.timeout', 10);
        $phpsessid = (string) $request->session()->get('external_auth.phpsessid', '');

        if (trim($phpsessid) === '') {
            return view('tickets.index', [
                'tickets' => [],
                'pagination' => null,
                'external_error' => "Session API manquante. Reconnectez-vous.",
            ]);
        }

        $page = max(1, (int) $request->query('page', 1));
        $vehicule = trim((string) $request->query('vehicule', ''));
        $usine = trim((string) $request->query('usine', ''));
        $agent = trim((string) $request->query('agent', ''));

        $queryParams = ['page' => $page];
        if ($vehicule !== '') {
            $queryParams['vehicule'] = $vehicule;
        }
        if ($usine !== '') {
            $queryParams['usine'] = $usine;
        }
        if ($agent !== '') {
            $queryParams['agent'] = $agent;
        }

        try {
            $response = Http::acceptJson()
                ->timeout($timeout)
                ->withHeaders([
                    'Cookie' => 'PHPSESSID=' . $phpsessid,
                ])
                ->get($mesTicketsUrl, $queryParams);
        } catch (\Throwable $e) {
            return view('tickets.index', [
                'tickets' => [],
                'pagination' => null,
                'external_error' => "Impossible de joindre le service tickets.",
            ]);
        }

        if (!$response->successful()) {
            $message = (string) ($response->json('error') ?? 'Erreur API.');

            return view('tickets.index', [
                'tickets' => [],
                'pagination' => null,
                'external_error' => $message,
            ]);
        }

        $tickets = $response->json('tickets');
        if (!is_array($tickets)) {
            $tickets = [];
        }

        // Filtrage côté Laravel si l'API ne supporte pas encore les filtres
        if ($vehicule !== '' || $usine !== '' || $agent !== '') {
            $tickets = array_filter($tickets, function ($t) use ($vehicule, $usine, $agent) {
                $match = true;
                if ($vehicule !== '') {
                    $matricule = strtolower($t['matricule_vehicule'] ?? '');
                    $match = $match && str_contains($matricule, strtolower($vehicule));
                }
                if ($usine !== '') {
                    $nomUsine = strtolower($t['nom_usine'] ?? '');
                    $match = $match && str_contains($nomUsine, strtolower($usine));
                }
                if ($agent !== '') {
                    $agentNom = strtolower(($t['agent_nom'] ?? '') . ' ' . ($t['agent_prenom'] ?? ''));
                    $match = $match && str_contains($agentNom, strtolower($agent));
                }
                return $match;
            });
            $tickets = array_values($tickets);
        }

        $pagination = $response->json('pagination');
        if (!is_array($pagination)) {
            $pagination = [
                'current_page' => 1,
                'per_page' => 20,
                'total' => count($tickets),
                'last_page' => 1,
            ];
        }

        // Récupérer la liste des véhicules pour l'autocomplétion
        $vehicules = [];
        try {
            $camionsResponse = Http::acceptJson()
                ->timeout($timeout)
                ->withHeaders(['Cookie' => 'PHPSESSID=' . $phpsessid])
                ->get($mesCamionsUrl);
            if ($camionsResponse->successful()) {
                $vehiculesData = $camionsResponse->json('vehicules');
                if (is_array($vehiculesData)) {
                    $vehicules = array_column($vehiculesData, 'matricule_vehicule');
                }
            }
        } catch (\Throwable $e) {
            // Ignorer l'erreur, on continue sans autocomplétion
        }

        return view('tickets.index', [
            'tickets' => $tickets,
            'pagination' => $pagination,
            'vehicules' => $vehicules,
            'external_error' => null,
        ]);
    }
}
