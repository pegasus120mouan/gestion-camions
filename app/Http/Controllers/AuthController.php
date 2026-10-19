<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AccesPlateforme;
use App\Services\ChefEquipeAuthService;
use App\Services\ChefEquipeSession;
use App\Services\ParametresAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin(ChefEquipeSession $chefSession)
    {
        if ($chefSession->check()) {
            return redirect()->route('dashboard');
        }

        return view('login');
    }

    public function login(
        Request $request,
        ChefEquipeAuthService $authService,
        ChefEquipeSession $chefSession,
        ParametresAccess $parametresAccess
    ) {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $acces = app(AccesPlateforme::class);

        if ($parametresAccess->attempt($credentials['login'], $credentials['password'])) {
            $this->connecterCommeChefPgf($request, $authService, $chefSession);
            $request->session()->regenerate();
            $parametresAccess->grant($request);
            $acces->memoriserProfil($request, 'superadmin');

            return redirect()->intended('/dashboard');
        }

        $gestionnaire = $this->attemptGestionnaire($credentials['login'], $credentials['password']);
        if ($gestionnaire) {
            $this->connecterCommeChefPgf($request, $authService, $chefSession);
            $request->session()->regenerate();
            Auth::login($gestionnaire, (bool) ($credentials['remember'] ?? false));
            $acces->memoriserProfil($request, 'gestionnaire');

            return redirect()->intended('/dashboard');
        }

        $chef = $authService->attempt($credentials['login'], $credentials['password']);

        if (!$chef) {
            return back()
                ->withErrors(['login' => 'Identifiants invalides.'])
                ->onlyInput('login');
        }

        $chefSession->login($request, $chef);
        $request->session()->regenerate();
        $profil = $acces->estChefPgf($chef) ? 'pgf' : 'chef_equipe';
        $acces->memoriserProfil($request, $profil);

        return redirect()->intended('/dashboard');
    }

    private function attemptGestionnaire(string $login, string $password): ?User
    {
        $user = User::query()
            ->where('role', 'gestionnaire')
            ->whereRaw('LOWER(TRIM(login)) = ?', [mb_strtolower(trim($login))])
            ->first();

        if (! $user || $password === '' || ! Hash::check($password, (string) $user->password)) {
            return null;
        }

        return $user;
    }

    private function connecterCommeChefPgf(
        Request $request,
        ChefEquipeAuthService $authService,
        ChefEquipeSession $chefSession
    ): void {
        $chef = $authService->findByLogin((string) config('services.superadmin.chef_login', 'pgf'))
            ?? $authService->findByToken((string) config('services.superadmin.chef_token', ''));

        if ($chef) {
            $chefSession->login($request, $chef);
        } else {
            $chefSession->loginSansChef($request);
        }
    }

    public function logout(Request $request, ChefEquipeSession $chefSession)
    {
        app(AccesPlateforme::class)->oublierProfil($request);
        $chefSession->logout($request);
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
