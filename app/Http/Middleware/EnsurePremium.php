<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePremium
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasPremium()) {
            abort(403, 'This feature requires an active premium entitlement.');
        }

        return $next($request);
    }
}
