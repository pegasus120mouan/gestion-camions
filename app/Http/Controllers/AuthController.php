<?php

namespace App\Http\Controllers;

use App\Services\ChefEquipeAuthService;
use App\Services\ChefEquipeSession;
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

    public function login(Request $request, ChefEquipeAuthService $authService, ChefEquipeSession $chefSession)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

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
