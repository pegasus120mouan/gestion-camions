<?php

namespace App\Http\Controllers;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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

        $loginUrl = (string) config('services.external_auth.login_url');
        $timeout = (int) config('services.external_auth.timeout', 10);

        $cookieJar = new CookieJar();

        try {
            $response = Http::asJson()
                ->acceptJson()
                ->timeout($timeout)
                ->withOptions(['cookies' => $cookieJar])
                ->post($loginUrl, [
                    'login' => $credentials['login'],
                    'password' => $credentials['password'],
                ]);
        } catch (\Throwable $e) {
            return back()
                ->withErrors(['login' => "Impossible de joindre le service d'authentification."])
                ->onlyInput('login');
        }

        if (! $response->successful()) {
            $message = (string) ($response->json('error') ?? 'Identifiants invalides.');

            return back()
                ->withErrors(['login' => $message])
                ->onlyInput('login');
        }

        $userData = $response->json('user');

        if (! is_array($userData)) {
            return back()
                ->withErrors(['login' => "Réponse invalide du service d'authentification."])
                ->onlyInput('login');
        }

        $externalLogin = (string) ($userData['login'] ?? $userData['contact'] ?? '');
        $externalContact = (string) ($userData['contact'] ?? '');

        if (trim($externalLogin) === '' && trim($externalContact) === '') {
            return back()
                ->withErrors(['login' => "Réponse invalide du service d'authentification."])
                ->onlyInput('login');
        }

        $localLogin = trim($externalLogin) !== '' ? trim($externalLogin) : trim($externalContact);

        $user = User::updateOrCreate(
            ['login' => $localLogin],
            [
                'name' => (string) ($userData['nom'] ?? $localLogin),
                'prenom' => (string) ($userData['prenoms'] ?? null),
                'contact' => trim($externalContact) !== '' ? trim($externalContact) : null,
                'role' => 'proprietaire',
                'password' => Str::random(32),
            ]
        );

        $apiUserId = (int) ($userData['id'] ?? 0);

        // Extraire PHPSESSID du CookieJar
        $phpsessid = null;
        foreach ($cookieJar->toArray() as $cookie) {
            if (($cookie['Name'] ?? '') === 'PHPSESSID') {
                $phpsessid = $cookie['Value'] ?? null;
                break;
            }
        }

        // Fallback: chercher dans les headers Set-Cookie
        if ($phpsessid === null) {
            $setCookies = $response->headers()['Set-Cookie'] ?? [];
            if (is_string($setCookies)) {
                $setCookies = [$setCookies];
            }
            foreach ((array) $setCookies as $setCookie) {
                if (is_string($setCookie) && preg_match('/PHPSESSID=([^;]+)/', $setCookie, $m) === 1) {
                    $phpsessid = $m[1];
                    break;
                }
            }
        }

        Auth::login($user, $remember);
        $request->session()->regenerate();

        // Stocker APRÈS regenerate() pour éviter la perte de données
        if ($apiUserId > 0) {
            $request->session()->put('external_auth.user_id', $apiUserId);
        }
        if ($phpsessid !== null && $phpsessid !== '') {
            $request->session()->put('external_auth.phpsessid', $phpsessid);
        }

        // DEBUG: log pour vérifier la capture du cookie
        \Log::info('External Auth Login', [
            'user_id' => $apiUserId,
            'phpsessid' => $phpsessid,
            'cookieJar' => $cookieJar->toArray(),
        ]);

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
