<?php

namespace App\Http\Middleware;

use App\Services\AccesPlateforme;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccesGestion
{
    public function __construct(
        private AccesPlateforme $acces,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->acces->peutGererAcces($request), 403);

        return $next($request);
    }
}
