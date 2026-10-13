<?php

namespace App\Http\Controllers;

use App\Models\FicheSortie;
use App\Services\MesTicketsService;
use Illuminate\Http\Request;

class ParametreController extends Controller
{
    public function index(Request $request, MesTicketsService $mesTicketsService)
    {
        try {
            $counts = $mesTicketsService->countsTicketsEnAttente($request);
        } catch (\Throwable) {
            $counts = ['total' => 0, 'professionnel' => 0, 'particulier' => 0];
        }

        return view('parametres.index', [
            'countsEnAttente' => $counts,
            'fichesNonDechargees' => FicheSortie::query()->whereNull('date_dechargement')->count(),
        ]);
    }
}
