<?php

namespace App\Http\Middleware;

use App\Services\ChefEquipeSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateChefEquipe
{
    public function __construct(
        private ChefEquipeSession $chefSession,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->chefSession->check($request)) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Non authentifié.'], 401);
            }

            return redirect()->guest(route('login'));
        }

        return $next($request);
    }
}
