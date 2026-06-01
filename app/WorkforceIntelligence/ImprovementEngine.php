<?php

namespace App\WorkforceIntelligence;

use App\Models\WorkforcePerformanceScore;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ImprovementEngine
{
    private const CACHE_TTL = 300;

    public function weekOverWeekDelta(int $userId, int $sedeId): ?array
    {
        $thisWeek = now()->startOfWeek()->toDateString();
        $lastWeek = now()->subWeek()->startOfWeek()->toDateString();

        return Cache::remember("wf_delta_{$userId}_{$sedeId}", self::CACHE_TTL, function () use ($userId, $sedeId, $thisWeek, $lastWeek) {
            $current  = WorkforcePerformanceScore::where('user_id', $userId)
                ->forSede($sedeId)->forPeriod('weekly', $thisWeek)->first();
            $previous = WorkforcePerformanceScore::where('user_id', $userId)
                ->forSede($sedeId)->forPeriod('weekly', $lastWeek)->first();

            if (!$current || !$previous) return null;

            return [
                'total_delta'        => $current->total_score - $previous->total_score,
                'productivity_delta' => $current->productivity_score - $previous->productivity_score,
                'quality_delta'      => $current->quality_score - $previous->quality_score,
                'discipline_delta'   => $current->discipline_score - $previous->discipline_score,
                'consistency_delta'  => $current->consistency_score - $previous->consistency_score,
                'current_score'      => $current->total_score,
                'previous_score'     => $previous->total_score,
            ];
        });
    }

    public function progressTrend(int $userId, int $sedeId, int $weeks = 4): Collection
    {
        $since = now()->subWeeks($weeks)->startOfWeek()->toDateString();

        return Cache::remember("wf_trend_{$userId}_{$sedeId}_{$weeks}", self::CACHE_TTL, fn () =>
            WorkforcePerformanceScore::where('user_id', $userId)
                ->forSede($sedeId)
                ->where('period_type', 'weekly')
                ->where('period_start', '>=', $since)
                ->orderBy('period_start')
                ->get(['period_start', 'total_score', 'productivity_score', 'quality_score', 'discipline_score', 'consistency_score'])
        );
    }

    public function teamImprovementRanking(int $sedeId): Collection
    {
        $thisWeek = now()->startOfWeek()->toDateString();
        $lastWeek = now()->subWeek()->startOfWeek()->toDateString();

        return Cache::remember("wf_team_improve_{$sedeId}", self::CACHE_TTL, function () use ($sedeId, $thisWeek, $lastWeek) {
            $current  = WorkforcePerformanceScore::forSede($sedeId)->forPeriod('weekly', $thisWeek)
                ->with('user:id,name')->get()->keyBy('user_id');
            $previous = WorkforcePerformanceScore::forSede($sedeId)->forPeriod('weekly', $lastWeek)
                ->get()->keyBy('user_id');

            return $current->map(function ($score) use ($previous) {
                $prev  = $previous->get($score->user_id);
                $delta = $prev ? $score->total_score - $prev->total_score : null;
                return [
                    'user_id'   => $score->user_id,
                    'user_name' => $score->user?->name,
                    'score'     => $score->total_score,
                    'delta'     => $delta,
                    'label'     => $score->performance_label,
                ];
            })->sortByDesc('score')->values();
        });
    }
}
