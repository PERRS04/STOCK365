<?php

namespace App\ExecutiveIntelligence;

use App\Models\CashClosing;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\CourtesyTransaction;
use App\Models\Sale;
use App\Models\StockAlert;
use App\Models\WorkforcePerformanceScore;
use App\OperationalIntelligence\Context\OperationalContextBuilder;
use App\OperationalIntelligence\Memory\HistoricalMemoryService;
use App\OperationalIntelligence\Scoring\HealthScoreEngine;
use App\OperationalIntelligence\Summaries\OperationalSummaryEngine;
use App\OperationalIntelligence\Trends\TrendAnalysisEngine;
use App\WorkforceIntelligence\ImprovementEngine;
use App\WorkforceIntelligence\WorkforceAnalyticsEngine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ExecutiveIntelligenceEngine
{
    private const CACHE_TTL = 60;
    private const CACHE_KEY = 'exec_snapshot';

    public function __construct(
        private readonly OperationalContextBuilder $context,
        private readonly OperationalSummaryEngine  $summaryEngine,
        private readonly HealthScoreEngine         $health,
        private readonly TrendAnalysisEngine       $trends,
        private readonly HistoricalMemoryService   $memory,
        private readonly WorkforceAnalyticsEngine  $workforce,
        private readonly ImprovementEngine         $improvement,
        private readonly ExecutivePriorityEngine   $priority,
        private readonly ExecutiveSummaryGenerator $generator,
    ) {}

    public function snapshot(bool $fresh = false): ExecutiveSnapshot
    {
        if ($fresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->compute());
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // ─── Computation ────────────────────────────────────────────────────────

    private function compute(): ExecutiveSnapshot
    {
        // ── 1. Operational context (all engines, pre-cached at 60s) ──────────
        $ctx    = $this->context->build();
        $digest = $this->summaryEngine->systemDigest();

        // ── 2. Health scores per sede ────────────────────────────────────────
        $healthScores = $this->health->scoreAll();
        $avgHealth    = (int) round($healthScores->avg('total') ?? 0);

        $criticalSedes = $healthScores->filter(fn ($s) => ($s['status'] ?? '') === 'critical')->count();
        $warningSedes  = $healthScores->filter(fn ($s) => ($s['status'] ?? '') === 'warning')->count();
        $healthySedes  = $healthScores->filter(fn ($s) => ($s['status'] ?? '') === 'healthy')->count();

        $globalStatus = match (true) {
            $criticalSedes > 0                => 'critical',
            $warningSedes > ($healthySedes)   => 'warning',
            $warningSedes > 0                 => 'warning',
            default                           => 'healthy',
        };

        // ── 3. Trend & delta ─────────────────────────────────────────────────
        $globalDelta = $healthScores->sum(fn ($s) =>
            $this->trends->deltaForSede($s['sede_id'], $s['total'])
        );
        $globalDelta = (int) round($globalDelta / max(1, $healthScores->count()));
        $globalTrend = match (true) {
            $globalDelta >= 5  => 'improving',
            $globalDelta <= -5 => 'declining',
            default            => 'stable',
        };

        // ── 4. Operational pressure (fast counts — no heavy joins) ──────────
        $inventoryAlerts   = StockAlert::where('alerta_activa', true)->count();
        $pendingMovements  = CashMovement::where('status', 'pendiente')->count();
        $pendingCourtesies = CourtesyTransaction::where('status', 'pendiente')->count();
        $pendingClosings   = CashClosing::where('estado', 'pendiente')->count();
        $pendingTotal      = $pendingMovements + $pendingCourtesies + $pendingClosings;
        $activeSessions    = CashSession::active()->count();

        // ── 5. Sales KPIs ────────────────────────────────────────────────────
        $salesToday = Sale::whereDate('fecha_venta', today())
            ->where('estado', 'completada')
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_sistema),0) as total')
            ->first();

        $salesMonth = (float) Sale::whereMonth('fecha_venta', now()->month)
            ->whereYear('fecha_venta', now()->year)
            ->where('estado', 'completada')
            ->sum('total_sistema');

        // ── 6. Workforce ─────────────────────────────────────────────────────
        $weekStart = now()->startOfWeek()->toDateString();
        $allScores = WorkforcePerformanceScore::where('period_type', 'weekly')
            ->where('period_start', $weekStart)
            ->with('user:id,name,sede_id')
            ->orderByDesc('total_score')
            ->get();

        $workforceAvg = $allScores->isEmpty() ? 0 : (int) round($allScores->avg('total_score'));
        $coachingNeeds = $allScores->filter(fn ($s) => $s->total_score < 55)->count();

        $topPerformerRaw    = $allScores->first();
        $topPerformer       = $this->mapPerformer($topPerformerRaw);

        $improvements       = $this->improvement->teamImprovementRanking(
            $allScores->first()?->sede_id ?? 0
        );
        $mostImprovedRaw    = $improvements->filter(fn ($r) => ($r['delta'] ?? 0) > 0)->first();
        $mostImproved       = $this->mapImprovedOperator($mostImprovedRaw);

        $consistent         = $this->workforce->mostConsistent(
            $allScores->first()?->sede_id ?? 0
        )->first();
        $mostConsistent     = $this->mapConsistentOperator($consistent);

        // ── 7. Sede map (per-sede executive view) ────────────────────────────
        $sedeMap = $this->buildSedeMap($healthScores);

        // ── 8. Priority engine ───────────────────────────────────────────────
        $allSummaries = $this->summaryEngine->summarize();
        $signals      = $allSummaries->filter(fn ($s) => !$s->isPositive());
        $positives    = $allSummaries->filter(fn ($s) => $s->isPositive());
        $topRisks   = $this->priority->topRisks($signals, $healthScores, $sedeMap);
        $topOpps    = $this->priority->topOpportunities($positives, $allScores, $healthScores);

        $topRisk        = $topRisks[0]  ?? null;
        $topImprovement = $topOpps[0]   ?? null;

        // ── 9. Executive narrative ────────────────────────────────────────────
        $sentences = $this->generator->generate(
            avgHealth:       $avgHealth,
            delta:           $globalDelta,
            trend:           $globalTrend,
            criticalSedes:   $criticalSedes,
            topRisks:        $topRisks,
            topOpps:         $topOpps,
            topPerformer:    $topPerformer,
            mostImproved:    $mostImproved,
            inventoryAlerts: $inventoryAlerts,
            pendingTotal:    $pendingTotal,
        );

        // ── 10. Signals critical count from context ───────────────────────────
        $criticalSignals = $ctx['signals']['critical'] ?? 0;

        return new ExecutiveSnapshot(
            globalHealthScore:       $avgHealth,
            globalHealthStatus:      $globalStatus,
            globalHealthDelta:       $globalDelta,
            globalTrend:             $globalTrend,
            criticalSedesCount:      $criticalSedes,
            warningSedesCount:       $warningSedes,
            healthySedesCount:       $healthySedes,
            totalSedesCount:         $healthScores->count(),
            topRisk:                 $topRisk,
            topImprovement:          $topImprovement,
            topPerformer:            $topPerformer,
            mostImprovedOperator:    $mostImproved,
            mostConsistentOperator:  $mostConsistent,
            workforceAvgScore:       $workforceAvg,
            coachingNeedsCount:      $coachingNeeds,
            inventoryAlertCount:     $inventoryAlerts,
            pendingApprovalsTotal:   $pendingTotal,
            activeSessionsCount:     $activeSessions,
            signalCriticalCount:     $criticalSignals,
            salesTodayTotal:         (float) ($salesToday->total ?? 0),
            salesTodayCount:         (int)   ($salesToday->cnt   ?? 0),
            salesMonthTotal:         $salesMonth,
            topRisks:                $topRisks,
            topOpportunities:        $topOpps,
            sedeMap:                 $sedeMap,
            executiveSentences:      $sentences,
            systemState:             $digest['state'],
            systemLabel:             $digest['state_label'],
            generatedAt:             now(),
        );
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function buildSedeMap($healthScores): array
    {
        $map = [];
        foreach ($healthScores as $s) {
            $sedeId = $s['sede_id'];
            $delta  = $this->trends->deltaForSede($sedeId, $s['total']);
            $trend  = match (true) {
                $delta >= 5  => 'improving',
                $delta <= -5 => 'declining',
                default      => 'stable',
            };

            $breakdown = $s['breakdown'] ?? [];
            $topIssue  = null;
            foreach (['cash', 'approvals', 'inventory', 'operational'] as $dim) {
                if (!empty($breakdown[$dim]['issues'])) {
                    $topIssue = $breakdown[$dim]['issues'][0];
                    break;
                }
            }

            $map[$sedeId] = [
                'sede_id'    => $sedeId,
                'sede_name'  => $s['sede_name'],
                'health'     => $s['total'],
                'status'     => $s['status'],
                'delta'      => $delta,
                'trend'      => $trend,
                'top_issue'  => $topIssue,
                'breakdown'  => [
                    'cash'        => $breakdown['cash']['score']        ?? 0,
                    'approvals'   => $breakdown['approvals']['score']   ?? 0,
                    'inventory'   => $breakdown['inventory']['score']   ?? 0,
                    'operational' => $breakdown['operational']['score'] ?? 0,
                ],
            ];
        }
        return $map;
    }

    private function mapPerformer(?object $score): ?array
    {
        if (!$score) return null;
        return [
            'user_id'     => $score->user_id,
            'user_name'   => $score->user?->name ?? '—',
            'score'       => $score->total_score,
            'label'       => $score->performance_label,
            'sede_id'     => $score->sede_id,
        ];
    }

    private function mapImprovedOperator(?array $row): ?array
    {
        if (!$row || ($row['delta'] ?? 0) <= 0) return null;
        return [
            'user_id'      => $row['user_id']   ?? null,
            'user_name'    => $row['user_name']  ?? '—',
            'delta'        => $row['delta']      ?? 0,
            'current_score'=> $row['score']      ?? 0,
        ];
    }

    private function mapConsistentOperator(?object $row): ?array
    {
        if (!$row) return null;
        return [
            'user_id'   => $row->user_id,
            'user_name' => $row->user?->name ?? '—',
            'avg_score' => round($row->avg_score ?? 0),
            'stddev'    => round($row->stddev_score ?? 0, 1),
        ];
    }
}
