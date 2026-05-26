<?php

namespace App\Http\Controllers;

use App\Services\SoldeChefEquipeService;
use Illuminate\Http\Request;

class SoldeChefEquipeController extends Controller
{
    public function index(Request $request, SoldeChefEquipeService $service)
    {
        $token = $this->resolveToken($request);
        $solde = null;
        $apiError = null;

        if ($token !== '') {
            $solde = $service->getSoldeByToken($token);
            if (!$solde) {
                $apiError = 'Aucun solde trouvé pour ce token. Vérifiez le token ou l\'API.';
            }
        }

        return view('solde_chef_equipe.index', [
            'token' => $token,
            'solde' => $solde,
            'apiError' => $apiError,
        ]);
    }

    public function updateToken(Request $request, SoldeChefEquipeService $service)
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:50'],
        ]);

        $token = trim($validated['token']);
        $request->session()->put('chef_equipe_token', $token);

        $user = $request->user();
        if ($user) {
            $user->chef_equipe_token = $token;
            $user->save();
        }

        $solde = $service->getSoldeByToken($token);
        if (!$solde) {
            return redirect()
                ->route('solde_chef_equipe.index')
                ->withInput()
                ->with('warning', 'Token enregistré, mais l\'API n\'a retourné aucun solde pour ce token.');
        }

        return redirect()
            ->route('solde_chef_equipe.index')
            ->with('success', 'Token enregistré. Solde chargé avec succès.');
    }

    public function show(Request $request, SoldeChefEquipeService $service)
    {
        $token = $this->resolveToken($request);

        if ($token === '') {
            return response()->json([
                'success' => false,
                'error' => 'Token chef d\'équipe manquant.',
            ], 422);
        }

        $solde = $service->getSoldeByToken($token);

        if (!$solde) {
            return response()->json([
                'success' => false,
                'error' => 'Solde introuvable pour ce token.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'solde' => $solde,
        ]);
    }

    private function resolveToken(Request $request): string
    {
        return trim((string) (
            $request->query('token')
            ?? $request->session()->get('chef_equipe_token')
            ?? $request->user()?->chef_equipe_token
            ?? config('services.external_auth.default_chef_equipe_token', '')
        ));
    }
}
