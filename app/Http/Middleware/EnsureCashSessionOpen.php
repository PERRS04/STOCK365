<?php

namespace App\Http\Middleware;

use App\Models\CashSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCashSessionOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isOperator()) {
            return $next($request);
        }

        $session = CashSession::activeForUser($user->id, $user->sede_id);

        if (!$session) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'    => 'no_cash_session',
                    'message'  => 'Debes abrir caja antes de realizar operaciones.',
                    'redirect' => route('cash-session.create'),
                ], 403);
            }

            return redirect()->route('cash-session.create')
                ->with('warning', 'Debes abrir caja antes de continuar.');
        }

        return $next($request);
    }
}
