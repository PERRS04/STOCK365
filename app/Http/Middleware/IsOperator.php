<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsOperator
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->isOperator()) {
            return $next($request);
        }

        abort(403, 'Solo operadores pueden acceder a esta sección');
    }
}
