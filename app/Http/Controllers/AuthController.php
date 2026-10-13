<?php

namespace App\Http\Controllers;

use App\Services\ChefEquipeAuthService;
use App\Services\ChefEquipeSession;
use App\Services\ParametresAccess;
use Illuminate\Http\Request;

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

        if ($parametresAccess->attempt($credentials['login'], $credentials['password'])) {
            $chef = $authService->findByLogin((string) config('services.superadmin.chef_login', ''));
            if ($chef) {
                $chefSession->login($request, $chef);
            } else {
                $chefSession->loginSansChef($request);
            }
            $request->session()->regenerate();
            $parametresAccess->grant($request);

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

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request, ChefEquipeSession $chefSession)
    {
        $chefSession->logout($request);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
