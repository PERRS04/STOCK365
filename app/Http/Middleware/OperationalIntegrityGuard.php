<?php

namespace App\Http\Middleware;

use App\Services\OperationalIntegrityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Operational Integrity Guard
 *
 * Intercepts ALL authenticated operator requests and checks for active
 * operational integrity issues (pending closings, abandoned sessions).
 *
 * When an issue is detected, the operator is redirected to the premium
 * operational lock screen instead of hitting a generic error or a confusing
 * "open caja" redirect from EnsureCashSessionOpen.
 *
 * Non-operators (boss, supervisor) bypass this middleware entirely.
 * Boss/supervisor resolve locks through the normal approval/force-close flows.
 *
 * Safe routes are ALWAYS allowed through — these are the routes the operator
 * needs to see the lock screen itself, escalate, log out, or check their status.
 */
class OperationalIntegrityGuard
{
    /**
     * Routes that always pass through regardless of operational state.
     * Keep this list minimal — every addition is a potential bypass.
     */
    private const SAFE_ROUTES = [
        'operational.lock',
        'operational.lock.escalate',
        'logout',
        'login',
        'cash-session.status',      // read-only — operator can always see their state
        // Standard Jetstream/Fortify auth routes
        'password.request',
        'password.email',
        'password.reset',
        'password.update',
        'verification.notice',
        'verification.verify',
        'verification.send',
    ];

    public function __construct(
        private readonly OperationalIntegrityService $integrityService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only operators are subject to operational integrity checks.
        // Boss and supervisor bypass entirely — they resolve locks via approvals.
        if (!$user || !$user->isOperator()) {
            return $next($request);
        }

        // Always allow safe routes — prevents redirect loops and keeps
        // the lock screen, logout, and status page accessible.
        if ($this->isSafeRoute($request)) {
            return $next($request);
        }

        $issue = $this->integrityService->evaluate($user);

        if ($issue === null) {
            return $next($request); // HEALTHY — proceed normally
        }

        // Issue detected — redirect to lock screen.
        // The lock screen controller will re-evaluate and render the full UI.
        if ($request->expectsJson()) {
            return response()->json([
                'error'    => 'operational_integrity_lock',
                'status'   => $issue->status->value,
                'type'     => $issue->type->value,
                'message'  => $issue->message(),
                'redirect' => route('operational.lock'),
            ], 423); // 423 Locked
        }

        return redirect()->route('operational.lock');
    }

    private function isSafeRoute(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        if ($routeName === null) {
            return false;
        }

        return in_array($routeName, self::SAFE_ROUTES, strict: true);
    }
}
