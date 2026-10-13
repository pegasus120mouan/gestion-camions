<?php

namespace App\Http\Middleware;

use App\Services\ParametresAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureParametresAccess
{
    public function __construct(
        private ParametresAccess $access,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->access->allowed($request), 403);

        return $next($request);
    }
}
