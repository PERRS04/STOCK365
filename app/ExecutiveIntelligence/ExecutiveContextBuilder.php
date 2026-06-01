<?php

namespace App\ExecutiveIntelligence;

use Illuminate\Support\Facades\Cache;

/**
 * Builds a structured, AI-ready context payload from the ExecutiveSnapshot.
 * No computation happens here — this class purely shapes already-computed data
 * into a format suitable for LLM consumption or advanced analytics.
 */
class ExecutiveContextBuilder
{
    private const CACHE_TTL = 120;

    public function __construct(
        private readonly ExecutiveIntelligenceEngine $engine,
    ) {}

    public function build(bool $fresh = false): array
    {
        if ($fresh) Cache::forget('exec_ai_context');

        return Cache::remember('exec_ai_context', self::CACHE_TTL, function () {
            $snap = $this->engine->snapshot();
            return $this->shape($snap);
        });
    }

    public function flush(): void
    {
        Cache::forget('exec_ai_context');
    }

    private function shape(ExecutiveSnapshot $snap): array
    {
        return [
            'meta' => [
                'generated_at'   => $snap->generatedAt->toIso8601String(),
                'system_state'   => $snap->systemState,
                'system_label'   => $snap->systemLabel,
                'cache_ttl_secs' => 120,
            ],

            'global_health' => [
                'score'           => $snap->globalHealthScore,
                'status'          => $snap->globalHealthStatus,
                'delta_4h'        => $snap->globalHealthDelta,
                'trend'           => $snap->globalTrend,
                'interpretation'  => $this->interpretHealth($snap),
            ],

            'sede_distribution' => [
                'total'    => $snap->totalSedesCount,
                'healthy'  => $snap->healthySedesCount,
                'warning'  => $snap->warningSedesCount,
                'critical' => $snap->criticalSedesCount,
            ],

            'operational_pressure' => [
                'level'                  => $snap->operationalPressure(),
                'pending_approvals'      => $snap->pendingApprovalsTotal,
                'inventory_alerts'       => $snap->inventoryAlertCount,
                'active_sessions'        => $snap->activeSessionsCount,
                'critical_signals'       => $snap->signalCriticalCount,
            ],

            'sales_kpis' => [
                'today_total'  => $snap->salesTodayTotal,
                'today_count'  => $snap->salesTodayCount,
                'month_total'  => $snap->salesMonthTotal,
            ],

            'workforce' => [
                'avg_score'       => $snap->workforceAvgScore,
                'coaching_needs'  => $snap->coachingNeedsCount,
                'top_performer'   => $snap->topPerformer,
                'most_improved'   => $snap->mostImprovedOperator,
                'most_consistent' => $snap->mostConsistentOperator,
            ],

            'priorities' => [
                'top_risks'         => $snap->topRisks,
                'top_opportunities' => $snap->topOpportunities,
                'top_risk'          => $snap->topRisk,
                'top_improvement'   => $snap->topImprovement,
            ],

            'sede_map' => $snap->sedeMap,

            'narrative' => $snap->executiveSentences,
        ];
    }

    private function interpretHealth(ExecutiveSnapshot $snap): string
    {
        if ($snap->criticalSedesCount > 0) {
            return "Operación bajo estrés — {$snap->criticalSedesCount} sede(s) en estado crítico requieren intervención.";
        }
        if ($snap->warningSedesCount > 0) {
            return "Operación monitoreada — {$snap->warningSedesCount} sede(s) en alerta, sin criticidad inmediata.";
        }
        if ($snap->globalHealthScore >= 85) {
            return "Operación en condiciones óptimas — todos los indicadores dentro de parámetros normales.";
        }
        return "Operación estable — sin alertas críticas activas.";
    }
}
