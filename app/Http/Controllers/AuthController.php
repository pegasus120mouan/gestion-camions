<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $remember = (bool) ($credentials['remember'] ?? false);

        // Rechercher l'utilisateur par login ou contact
        $user = User::where('login', $credentials['login'])
            ->orWhere('contact', $credentials['login'])
            ->first();

        if (!$user) {
            return back()
                ->withErrors(['login' => 'Identifiants invalides.'])
                ->onlyInput('login');
        }

        // Vérifier le mot de passe
        if (!Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withErrors(['login' => 'Identifiants invalides.'])
                ->onlyInput('login');
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
