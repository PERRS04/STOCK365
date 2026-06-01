<?php

namespace App\WorkforceIntelligence;

use App\Models\CashClosing;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Sale;
use App\Models\User;
use App\Models\WorkforcePerformanceScore;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WorkforceAnalyticsEngine
{
    private const DEPOSIT_TYPES   = ['deposito', 'envio_boveda'];
    private const CACHE_TTL       = 300;

    // ─── Score computation ───────────────────────────────────────────────────

    public function computeForUser(
        int    $userId,
        int    $sedeId,
        string $periodType,
        Carbon $periodStart,
        Carbon $periodEnd
    ): WorkforcePerformanceScore {
        [$prodScore, $prodMeta] = $this->computeProductivity($userId, $sedeId, $periodStart, $periodEnd);
        [$qualScore, $qualMeta] = $this->computeQuality($userId, $sedeId, $periodStart, $periodEnd);
        [$discScore, $discMeta] = $this->computeDiscipline($userId, $sedeId, $periodStart, $periodEnd);
        [$consScore, $consMeta] = $this->computeConsistency($userId, $sedeId, $periodStart, $periodEnd);

        $total = (int) round(
            $prodScore * 0.25 +
            $qualScore * 0.30 +
            $discScore * 0.25 +
            $consScore * 0.20
        );

        $label = $this->scoreLabel($total);

        return WorkforcePerformanceScore::updateOrCreate(
            [
                'user_id'      => $userId,
                'period_type'  => $periodType,
                'period_start' => $periodStart->toDateString(),
            ],
            array_merge($prodMeta, $qualMeta, $discMeta, $consMeta, [
                'sede_id'           => $sedeId,
                'period_end'        => $periodEnd->toDateString(),
                'productivity_score' => $prodScore,
                'quality_score'      => $qualScore,
                'discipline_score'   => $discScore,
                'consistency_score'  => $consScore,
                'total_score'        => $total,
                'performance_label'  => $label,
                'computed_at'        => now(),
            ])
        );
    }

    // ─── Leaderboard & insights ──────────────────────────────────────────────

    public function leaderboard(int $sedeId, string $periodType, string $periodStart): Collection
    {
        return Cache::remember("wf_lb_{$sedeId}_{$periodType}_{$periodStart}", self::CACHE_TTL, fn () =>
            WorkforcePerformanceScore::forSede($sedeId)
                ->forPeriod($periodType, $periodStart)
                ->with('user:id,name')
                ->orderByDesc('total_score')
                ->get()
        );
    }

    public function mostImproved(int $sedeId): Collection
    {
        $thisWeek = now()->startOfWeek()->toDateString();
        $lastWeek = now()->subWeek()->startOfWeek()->toDateString();

        return Cache::remember("wf_improved_{$sedeId}", self::CACHE_TTL, function () use ($sedeId, $thisWeek, $lastWeek) {
            $current  = WorkforcePerformanceScore::forSede($sedeId)->forPeriod('weekly', $thisWeek)
                ->pluck('total_score', 'user_id');
            $previous = WorkforcePerformanceScore::forSede($sedeId)->forPeriod('weekly', $lastWeek)
                ->pluck('total_score', 'user_id');

            return collect($current)
                ->map(fn ($score, $uid) => [
                    'user_id' => $uid,
                    'delta'   => $score - ($previous[$uid] ?? $score),
                    'score'   => $score,
                ])
                ->filter(fn ($r) => $r['delta'] > 0)
                ->sortByDesc('delta')
                ->values();
        });
    }

    public function coachingOpportunities(int $sedeId): Collection
    {
        $periodStart = now()->startOfWeek()->toDateString();

        return Cache::remember("wf_coaching_{$sedeId}", self::CACHE_TTL, fn () =>
            WorkforcePerformanceScore::forSede($sedeId)
                ->forPeriod('weekly', $periodStart)
                ->with('user:id,name')
                ->where('total_score', '<', 55)
                ->orderBy('total_score')
                ->get()
        );
    }

    public function mostConsistent(int $sedeId, int $weeks = 4): Collection
    {
        $since = now()->subWeeks($weeks)->startOfWeek()->toDateString();

        return Cache::remember("wf_consistent_{$sedeId}_{$weeks}", self::CACHE_TTL, fn () =>
            WorkforcePerformanceScore::forSede($sedeId)
                ->where('period_type', 'weekly')
                ->where('period_start', '>=', $since)
                ->with('user:id,name')
                ->select('user_id', DB::raw('AVG(total_score) as avg_score'), DB::raw('STDDEV(total_score) as stddev_score'), DB::raw('COUNT(*) as weeks_count'))
                ->groupBy('user_id')
                ->having('weeks_count', '>=', 2)
                ->orderBy('stddev_score')
                ->get()
        );
    }

    public function teamHealth(int $sedeId, string $periodType, string $periodStart): array
    {
        return Cache::remember("wf_team_{$sedeId}_{$periodType}_{$periodStart}", self::CACHE_TTL, function () use ($sedeId, $periodType, $periodStart) {
            $scores = WorkforcePerformanceScore::forSede($sedeId)
                ->forPeriod($periodType, $periodStart)
                ->get(['total_score', 'productivity_score', 'quality_score', 'discipline_score', 'consistency_score']);

            if ($scores->isEmpty()) {
                return ['count' => 0, 'avg' => 0, 'distribution' => []];
            }

            return [
                'count'       => $scores->count(),
                'avg'         => round($scores->avg('total_score'), 1),
                'avg_breakdown' => [
                    'productivity' => round($scores->avg('productivity_score'), 1),
                    'quality'      => round($scores->avg('quality_score'), 1),
                    'discipline'   => round($scores->avg('discipline_score'), 1),
                    'consistency'  => round($scores->avg('consistency_score'), 1),
                ],
                'distribution' => [
                    'excellence'   => $scores->filter(fn ($s) => $s->total_score >= 85)->count(),
                    'strong'       => $scores->filter(fn ($s) => $s->total_score >= 70 && $s->total_score < 85)->count(),
                    'solid'        => $scores->filter(fn ($s) => $s->total_score >= 55 && $s->total_score < 70)->count(),
                    'attention'    => $scores->filter(fn ($s) => $s->total_score >= 40 && $s->total_score < 55)->count(),
                    'coaching'     => $scores->filter(fn ($s) => $s->total_score < 40)->count(),
                ],
            ];
        });
    }

    // ─── Internal scoring ────────────────────────────────────────────────────

    private function computeProductivity(int $userId, int $sedeId, Carbon $start, Carbon $end): array
    {
        $userSales = Sale::where('user_id', $userId)
            ->where('sede_id', $sedeId)
            ->whereBetween('created_at', [$start, $end])
            ->where('estado', 'completada')
            ->count();

        $volume = Sale::where('user_id', $userId)
            ->where('sede_id', $sedeId)
            ->whereBetween('created_at', [$start, $end])
            ->where('estado', 'completada')
            ->sum('total_sistema');

        $sessions = CashSession::where('user_id', $userId)
            ->where('sede_id', $sedeId)
            ->whereBetween('opened_at', [$start, $end])
            ->count();

        $sedeOperatorCount = User::where('sede_id', $sedeId)->where('role', 'operador')->count();
        $sedeTotal = Sale::where('sede_id', $sedeId)
            ->whereBetween('created_at', [$start, $end])
            ->where('estado', 'completada')
            ->count();
        $sedeAvg = $sedeOperatorCount > 0 ? $sedeTotal / $sedeOperatorCount : max(1, $userSales);

        $score = $sedeAvg > 0
            ? (int) min(100, round(($userSales / $sedeAvg) * 100))
            : ($userSales > 0 ? 70 : 0);

        return [$score, [
            'total_sales'   => $userSales,
            'sales_volume'  => $volume,
            'session_count' => $sessions,
        ]];
    }

    private function computeQuality(int $userId, int $sedeId, Carbon $start, Carbon $end): array
    {
        $closings = CashClosing::where('user_id', $userId)
            ->where('sede_id', $sedeId)
            ->whereBetween('fecha_cierre', [$start, $end])
            ->get(['diferencia', 'total_sistema', 'estado']);

        if ($closings->isEmpty()) {
            return [60, ['closings_count' => 0, 'exact_closings' => 0, 'avg_cash_difference_pct' => 0]];
        }

        $avgDiffPct = $closings
            ->filter(fn ($c) => $c->total_sistema > 0)
            ->avg(fn ($c) => abs($c->diferencia) / $c->total_sistema * 100) ?? 0;

        $exactClosings = $closings->filter(fn ($c) => abs($c->diferencia) < 0.01)->count();

        $score = match (true) {
            $avgDiffPct < 0.01 => 100,
            $avgDiffPct < 0.5  => 92,
            $avgDiffPct < 1.0  => 80,
            $avgDiffPct < 2.0  => 65,
            $avgDiffPct < 5.0  => 40,
            default            => 15,
        };

        return [$score, [
            'closings_count'          => $closings->count(),
            'exact_closings'          => $exactClosings,
            'avg_cash_difference_pct' => round($avgDiffPct, 4),
        ]];
    }

    private function computeDiscipline(int $userId, int $sedeId, Carbon $start, Carbon $end): array
    {
        $sessions = CashSession::where('user_id', $userId)
            ->where('sede_id', $sedeId)
            ->whereBetween('opened_at', [$start, $end])
            ->get(['id', 'opened_at', 'closed_at', 'status']);

        $unclosed = $sessions->filter(
            fn ($s) => $s->status === 'open' && $s->opened_at->diffInHours(now(), true) >= 20
        )->count();

        $depositMovements = CashMovement::where('user_id', $userId)
            ->where('sede_id', $sedeId)
            ->whereIn('type', self::DEPOSIT_TYPES)
            ->whereBetween('created_at', [$start, $end])
            ->with('cashSession:id,opened_at')
            ->get();

        $totalDeposits = $depositMovements->count();
        $lateDeposits  = $depositMovements->filter(function ($mov) {
            if (!$mov->cashSession) return false;
            return $mov->cashSession->opened_at->diffInHours($mov->created_at) >= 4;
        })->count();

        $depositScore = $totalDeposits > 0
            ? max(0, 100 - ($lateDeposits / $totalDeposits) * 100)
            : 80;

        $closingScore = $sessions->count() > 0
            ? max(0, 100 - ($unclosed / $sessions->count()) * 100)
            : 80;

        $score = (int) round($depositScore * 0.55 + $closingScore * 0.45);

        return [$score, [
            'late_deposits'     => $lateDeposits,
            'total_deposits'    => $totalDeposits,
            'unclosed_sessions' => $unclosed,
        ]];
    }

    private function computeConsistency(int $userId, int $sedeId, Carbon $start, Carbon $end): array
    {
        $dailySales = Sale::where('user_id', $userId)
            ->where('sede_id', $sedeId)
            ->whereBetween('created_at', [$start, $end])
            ->where('estado', 'completada')
            ->selectRaw('DATE(created_at) as day, COUNT(*) as cnt')
            ->groupBy('day')
            ->pluck('cnt')
            ->toArray();

        if (count($dailySales) < 2) {
            return [70, ['score_variance' => 0]];
        }

        $mean     = array_sum($dailySales) / count($dailySales);
        $variance = array_sum(array_map(fn ($v) => pow($v - $mean, 2), $dailySales)) / count($dailySales);
        $stddev   = sqrt($variance);

        $normalizedVariance = $mean > 0 ? ($stddev / $mean) * 100 : 0;
        $score = (int) max(0, min(100, 100 - $normalizedVariance * 1.2));

        return [$score, ['score_variance' => round($normalizedVariance, 4)]];
    }

    public function scoreLabel(int $score): string
    {
        return match (true) {
            $score >= 85 => 'Operational Excellence',
            $score >= 70 => 'Strong Performance',
            $score >= 55 => 'Solid',
            $score >= 40 => 'Needs Attention',
            default      => 'Needs Coaching',
        };
    }
}
