<?php

namespace App\Services\OperationalIntelligence;

use App\Enums\OperationalAlertType;
use App\Enums\OperationalStatus;
use App\Models\ActivityLog;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\CourtesyTransaction;
use App\Models\StockAlert;
use App\Values\OperationalAlert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * AlertDetectionService
 *
 * Central intelligence engine for the admin/supervisor command center.
 * Detects all actionable operational anomalies across all sedes and
 * returns a sorted, deduplicated Collection of OperationalAlert objects.
 *
 * Design notes:
 * ─────────────
 * • 15-second shared cache prevents redundant queries when multiple
 *   Livewire components poll simultaneously.
 * • No LiveCashBoxService calls — this service stays cheap (5 queries max).
 *   Cash balance anomalies are surfaced by BossLiveOverview instead.
 * • Detection rules are threshold-based and conservative — noisy alerts
 *   are worse than missed ones for operational staff.
 */
class AlertDetectionService
{
    private const ABANDON_THRESHOLD_HOURS   = 16;
    private const STALE_MOVEMENT_MINUTES    = 30;
    private const STALE_COURTESY_MINUTES    = 45;
    private const ESCALATION_LOOKBACK_HOURS = 2;
    private const CACHE_TTL_SECONDS         = 15;
    private const CACHE_KEY                 = 'op_intelligence_alerts_v2';

    public function detectAll(): Collection
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            return $this->buildAlerts();
        });
    }

    /** Force a cache miss on next call — use after boss actions (force close, approve). */
    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // ─── Alert building ─────────────────────────────────────────────────────

    private function buildAlerts(): Collection
    {
        $alerts = collect();

        // ── 1. Active sessions: abandoned + pending closing ──────────────────
        $sessions = CashSession::whereIn('status', ['open', 'pending_closing'])
            ->with('user:id,name', 'sede:id,nombre')
            ->get();

        foreach ($sessions as $session) {
            if ($session->isOpen() && $this->isAbandoned($session)) {
                $alerts->push($this->buildAbandonedAlert($session));
            }
            if ($session->isPendingClosing()) {
                $alerts->push($this->buildPendingClosingAlert($session));
            }
        }

        // ── 2. Operator escalation requests (locked operators asking for help) ─
        ActivityLog::where('action', 'operational_lock_escalation')
            ->where('created_at', '>=', now()->subHours(self::ESCALATION_LOOKBACK_HOURS))
            ->latest()
            ->limit(5)
            ->get()
            ->each(fn ($log) => $alerts->push($this->buildEscalationAlert($log)));

        // ── 3. Active stock alerts (aggregated per sede) ─────────────────────
        StockAlert::where('alerta_activa', true)
            ->with('sede:id,nombre')
            ->get()
            ->groupBy('sede_id')
            ->each(function ($sedeAlerts, $sedeId) use ($alerts) {
                $alerts->push($this->buildStockAlert((int) $sedeId, $sedeAlerts));
            });

        // ── 4. Stale pending cash movements ──────────────────────────────────
        $staleMov = CashMovement::where('status', 'pendiente')
            ->where('created_at', '<=', now()->subMinutes(self::STALE_MOVEMENT_MINUTES))
            ->count();

        if ($staleMov > 0) {
            $alerts->push($this->buildStalePendingAlert(
                id:       'stale_movements',
                type:     OperationalAlertType::STALE_PENDING_MOVEMENT,
                count:    $staleMov,
                routeKey: 'approvals.index',
            ));
        }

        // ── 5. Stale pending courtesy transactions ───────────────────────────
        $staleCourtesy = CourtesyTransaction::where('status', 'pendiente')
            ->where('created_at', '<=', now()->subMinutes(self::STALE_COURTESY_MINUTES))
            ->count();

        if ($staleCourtesy > 0) {
            $alerts->push($this->buildStalePendingAlert(
                id:       'stale_courtesies',
                type:     OperationalAlertType::STALE_PENDING_COURTESY,
                count:    $staleCourtesy,
                routeKey: 'approvals.index',
            ));
        }

        // ── Sort: CRITICAL first, then WARNING; within each group by recency ─
        return $alerts->sortBy([
            fn (OperationalAlert $a) => match($a->severity) {
                OperationalStatus::CRITICAL => 0,
                OperationalStatus::WARNING  => 1,
                default                     => 2,
            },
            fn (OperationalAlert $a) => -$a->detectedAt->timestamp,
        ])->values();
    }

    // ─── Builders ───────────────────────────────────────────────────────────

    private function buildAbandonedAlert(CashSession $session): OperationalAlert
    {
        $hours = $session->opened_at->diffInHours(now());

        return new OperationalAlert(
            id:           "abandoned_{$session->id}",
            type:         OperationalAlertType::ABANDONED_SESSION,
            severity:     OperationalStatus::CRITICAL,
            title:        'Sesión de caja abandonada',
            subtitle:     $session->isOpen() && !$session->opened_at->isToday()
                ? 'Abierta desde ' . $session->opened_at->locale('es')->isoFormat('ddd D MMM [a las] H:mm')
                : "Activa hace {$hours} horas sin cierre registrado",
            sedeId:       $session->sede_id,
            sedeName:     $session->sede?->nombre,
            operatorId:   $session->user_id,
            operatorName: $session->user?->name,
            detectedAt:   $session->opened_at,
            payload:      [
                'session_id'   => $session->id,
                'opened_at'    => $session->opened_at->toIso8601String(),
                'duration'     => $session->duration,
                'view_route'   => route('cash-sessions.index'),
                'force_close'  => true,
            ],
        );
    }

    private function buildPendingClosingAlert(CashSession $session): OperationalAlert
    {
        $closingTime = $session->cashClosings()->latest()->first()?->fecha_cierre;
        $ago = $closingTime ? $closingTime->diffForHumans() : 'hace un momento';

        return new OperationalAlert(
            id:           "pending_closing_{$session->id}",
            type:         OperationalAlertType::PENDING_CLOSING,
            severity:     OperationalStatus::WARNING,
            title:        'Cierre pendiente de aprobación',
            subtitle:     "Enviado {$ago} · esperando supervisor",
            sedeId:       $session->sede_id,
            sedeName:     $session->sede?->nombre,
            operatorId:   $session->user_id,
            operatorName: $session->user?->name,
            detectedAt:   $closingTime ?? $session->opened_at,
            payload:      [
                'session_id'  => $session->id,
                'action_route' => route('cash-closings.pending'),
            ],
        );
    }

    private function buildEscalationAlert(ActivityLog $log): OperationalAlert
    {
        $sessionId = $log->new_values['session_id'] ?? null;

        return new OperationalAlert(
            id:           "escalation_{$log->id}",
            type:         OperationalAlertType::OPERATOR_ESCALATION,
            severity:     OperationalStatus::CRITICAL,
            title:        'Operador solicita autorización',
            subtitle:     $log->user_name . ' está bloqueado y requiere intervención',
            sedeId:       $log->sede_id,
            sedeName:     null,
            operatorId:   $log->user_id,
            operatorName: $log->user_name,
            detectedAt:   $log->created_at,
            payload:      [
                'session_id'  => $sessionId,
                'log_id'      => $log->id,
                'force_close' => $sessionId !== null,
                'view_route'  => route('cash-sessions.index'),
            ],
        );
    }

    private function buildStockAlert(int $sedeId, Collection $sedeAlerts): OperationalAlert
    {
        $first    = $sedeAlerts->first();
        $sedeName = $first->sede?->nombre ?? "Sede #{$sedeId}";
        $count    = $sedeAlerts->count();

        return new OperationalAlert(
            id:           "stock_{$sedeId}",
            type:         OperationalAlertType::CRITICAL_STOCK,
            severity:     OperationalStatus::WARNING,
            title:        "Stock crítico — {$sedeName}",
            subtitle:     "{$count} producto" . ($count > 1 ? 's' : '') . ' bajo el mínimo operacional',
            sedeId:       $sedeId,
            sedeName:     $sedeName,
            operatorId:   null,
            operatorName: null,
            detectedAt:   now(),
            payload:      [
                'count'       => $count,
                'action_route' => route('inventory.index'),
            ],
        );
    }

    private function buildStalePendingAlert(
        string $id,
        OperationalAlertType $type,
        int $count,
        string $routeKey,
    ): OperationalAlert {
        $minutes = $type === OperationalAlertType::STALE_PENDING_MOVEMENT
            ? self::STALE_MOVEMENT_MINUTES
            : self::STALE_COURTESY_MINUTES;

        return new OperationalAlert(
            id:           $id,
            type:         $type,
            severity:     OperationalStatus::WARNING,
            title:        $type->label(),
            subtitle:     "{$count} solicitud" . ($count > 1 ? 'es' : '') . " sin respuesta hace más de {$minutes} min",
            sedeId:       null,
            sedeName:     null,
            operatorId:   null,
            operatorName: null,
            detectedAt:   now()->subMinutes($minutes),
            payload:      [
                'count'        => $count,
                'action_route' => route($routeKey),
            ],
        );
    }

    // ─── Helpers ────────────────────────────────────────────────────────────

    private function isAbandoned(CashSession $session): bool
    {
        return !$session->opened_at->isToday()
            || $session->opened_at->diffInHours(now()) >= self::ABANDON_THRESHOLD_HOURS;
    }
}
