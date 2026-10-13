<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ParametresAccess
{
    public const SESSION_KEY = 'superadmin';

    public function attempt(string $login, string $password): bool
    {
        $expectedLogin = trim((string) config('services.superadmin.login', ''));
        $expectedPassword = (string) config('services.superadmin.password', '');

        if ($expectedLogin === '' || $expectedPassword === '' || $password === '') {
            return false;
        }

        if (mb_strtolower(trim($login)) !== mb_strtolower($expectedLogin)) {
            return false;
        }

        if (str_starts_with($expectedPassword, '$2y$') || str_starts_with($expectedPassword, '$argon')) {
            return Hash::check($password, $expectedPassword);
        }

        return hash_equals($expectedPassword, $password);
    }

    public function grant(Request $request): void
    {
        $request->session()->put(self::SESSION_KEY, true);
    }

    public function allowed(?Request $request = null): bool
    {
        $request ??= request();

        return $request->hasSession() && (bool) $request->session()->get(self::SESSION_KEY, false);
    }
}
