<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\OperationalIntegrityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OperationalLockController extends Controller
{
    /**
     * Escalation rate limit: one request per N minutes per operator.
     */
    private const ESCALATION_COOLDOWN_MINUTES = 15;

    public function __construct(
        private readonly OperationalIntegrityService $integrityService,
    ) {}

    /**
     * Show the operational lock screen.
     * Re-evaluates state fresh on every load — no stale session data.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // Boss/supervisor have no business seeing the lock screen
        if ($user->isAdminLevel()) {
            return redirect()->route('dashboard');
        }

        $issue = $this->integrityService->evaluate($user);

        // No active issues → user should not be here
        if ($issue === null) {
            return redirect()->route('dashboard');
        }

        return view('operator.operational-lock', [
            'user'             => $user,
            'issue'            => $issue,
            'cashSession'      => $issue->session->load(['sede']),
            'escalationSent'   => session('operational_lock_escalated', false),
            'escalationCooldown' => $this->getEscalationCooldown($request),
        ]);
    }

    /**
     * Record an escalation request from the locked operator.
     * Notifies the supervisor/boss via the activity log.
     * Rate-limited to prevent alert fatigue.
     */
    public function escalate(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Re-validate — don't process escalations when there's no actual issue
        $issue = $this->integrityService->evaluate($user);

        if ($issue === null) {
            return redirect()->route('dashboard');
        }

        // Rate limit check
        $lastEscalation = session('operational_lock_escalated_at');
        if ($lastEscalation && now()->diffInMinutes($lastEscalation) < self::ESCALATION_COOLDOWN_MINUTES) {
            return redirect()->route('operational.lock')
                ->with('operational_lock_escalated', true); // already sent, show same confirmation
        }

        // Write to activity log — boss/supervisor will see this in their activity feed
        ActivityLog::create([
            'user_id'     => $user->id,
            'user_name'   => $user->name,
            'user_role'   => 'operador',
            'sede_id'     => $user->sede_id,
            'action'      => 'operational_lock_escalation',
            'model_type'  => 'CashSession',
            'model_id'    => $issue->session->id,
            'description' => "Operador {$user->name} solicitó autorización de supervisor desde bloqueo operacional.",
            'new_values'  => [
                'issue_type'   => $issue->type->value,
                'severity'     => $issue->status->value,
                'session_id'   => $issue->session->id,
                'opened_at'    => $issue->session->opened_at->toIso8601String(),
                'ip'           => $request->ip(),
            ],
            'ip_address'  => $request->ip(),
        ]);

        // Mark in session to show confirmation UI and enforce cooldown
        session([
            'operational_lock_escalated'    => true,
            'operational_lock_escalated_at' => now(),
        ]);

        return redirect()->route('operational.lock')
            ->with('operational_lock_escalated', true);
    }

    private function getEscalationCooldown(Request $request): int
    {
        $lastEscalation = session('operational_lock_escalated_at');

        if (!$lastEscalation) {
            return 0;
        }

        $elapsed = now()->diffInMinutes($lastEscalation);

        return max(0, self::ESCALATION_COOLDOWN_MINUTES - $elapsed);
    }
}
