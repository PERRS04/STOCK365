<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsBoss
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->isBoss()) {
            return $next($request);
        }

        abort(403, 'Solo administradores pueden acceder a esta sección');
    }
}
