<?php

namespace App\ExecutiveIntelligence;

use Illuminate\Support\Carbon;

/**
 * Immutable Executive Snapshot DTO.
 * All data is pre-computed — this is a pure value object.
 */
readonly class ExecutiveSnapshot
{
    public function __construct(
        // ── Global operational health ─────────────────────────────────────────
        public int    $globalHealthScore,
        public string $globalHealthStatus,   // healthy | warning | critical
        public int    $globalHealthDelta,     // vs 4h ago
        public string $globalTrend,           // improving | declining | stable | unknown

        // ── Sede distribution ────────────────────────────────────────────────
        public int $criticalSedesCount,
        public int $warningSedesCount,
        public int $healthySedesCount,
        public int $totalSedesCount,

        // ── Top risk and improvement ─────────────────────────────────────────
        public ?array $topRisk,              // {sedeId, sedeName, type, message, severity, action}
        public ?array $topImprovement,       // {sedeId, sedeName, delta, message}

        // ── Workforce executive summary ───────────────────────────────────────
        public ?array $topPerformer,         // {userId, userName, score, label, sedeNombre}
        public ?array $mostImprovedOperator, // {userId, userName, delta, currentScore, sedeNombre}
        public ?array $mostConsistentOperator,// {userId, userName, avgScore, stddev}
        public int    $workforceAvgScore,    // 0–100
        public int    $coachingNeedsCount,

        // ── Operational pressure ─────────────────────────────────────────────
        public int   $inventoryAlertCount,
        public int   $pendingApprovalsTotal,  // movements + courtesies + closings
        public int   $activeSessionsCount,
        public int   $signalCriticalCount,

        // ── Sales KPIs (today) ────────────────────────────────────────────────
        public float $salesTodayTotal,
        public int   $salesTodayCount,
        public float $salesMonthTotal,

        // ── Priority lists ────────────────────────────────────────────────────
        public array $topRisks,          // max 3 items: {type, message, sedeName, severity, action}
        public array $topOpportunities,  // max 3 items: {type, message, detail}

        // ── Per-sede executive map ────────────────────────────────────────────
        public array $sedeMap,           // keyed by sede_id, each has health, trend, delta, topIssue

        // ── Narrative sentences ───────────────────────────────────────────────
        public array $executiveSentences, // 5–7 Spanish sentences

        // ── System state ─────────────────────────────────────────────────────
        public string $systemState,   // optimal | normal | degraded | critical
        public string $systemLabel,   // ÓPTIMO | NORMAL | BAJO MONITOREO | CRÍTICO

        public Carbon $generatedAt,
    ) {}

    public function isHealthy(): bool
    {
        return $this->globalHealthStatus === 'healthy';
    }

    public function hasCriticalSedes(): bool
    {
        return $this->criticalSedesCount > 0;
    }

    public function operationalPressure(): string
    {
        $pending = $this->pendingApprovalsTotal;
        return match (true) {
            $pending === 0                => 'none',
            $pending <= 3                 => 'low',
            $pending <= 8                 => 'medium',
            default                       => 'high',
        };
    }

    public function toArray(): array
    {
        return [
            'global_health_score'       => $this->globalHealthScore,
            'global_health_status'      => $this->globalHealthStatus,
            'global_health_delta'       => $this->globalHealthDelta,
            'global_trend'              => $this->globalTrend,
            'critical_sedes'            => $this->criticalSedesCount,
            'warning_sedes'             => $this->warningSedesCount,
            'healthy_sedes'             => $this->healthySedesCount,
            'total_sedes'               => $this->totalSedesCount,
            'top_risk'                  => $this->topRisk,
            'top_improvement'           => $this->topImprovement,
            'top_performer'             => $this->topPerformer,
            'most_improved_operator'    => $this->mostImprovedOperator,
            'workforce_avg_score'       => $this->workforceAvgScore,
            'coaching_needs_count'      => $this->coachingNeedsCount,
            'inventory_alert_count'     => $this->inventoryAlertCount,
            'pending_approvals_total'   => $this->pendingApprovalsTotal,
            'active_sessions_count'     => $this->activeSessionsCount,
            'signal_critical_count'     => $this->signalCriticalCount,
            'sales_today_total'         => $this->salesTodayTotal,
            'sales_today_count'         => $this->salesTodayCount,
            'sales_month_total'         => $this->salesMonthTotal,
            'top_risks'                 => $this->topRisks,
            'top_opportunities'         => $this->topOpportunities,
            'sede_map'                  => $this->sedeMap,
            'executive_sentences'       => $this->executiveSentences,
            'system_state'              => $this->systemState,
            'system_label'              => $this->systemLabel,
            'generated_at'              => $this->generatedAt->toIso8601String(),
        ];
    }
}
