<?php

namespace App\OperationalIntelligence\Trends;

use App\Enums\TrendDirection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class TrendAnalysisEngine
{
    // Snapshots kept 26h so we can detect 24h-back trends
    private const SNAPSHOT_TTL = 26 * 3600;

    /**
     * Store the current score for this sede and return the trend direction
     * relative to 4 hours ago.
     */
    public function trendForSede(int $sedeId, int $currentScore): TrendDirection
    {
        $this->storeSnapshot($sedeId, $currentScore);
        return $this->computeTrend($sedeId, $currentScore);
    }

    public function deltaForSede(int $sedeId, int $currentScore): int
    {
        $previousKey = $this->hourKey($sedeId, now()->subHours(4));
        $previous    = Cache::get($previousKey);

        return $previous !== null ? ($currentScore - (int) $previous) : 0;
    }

    // ─── Internal ───────────────────────────────────────────────────────────

    private function storeSnapshot(int $sedeId, int $score): void
    {
        // Use Cache::add so we only store once per hour (first call wins)
        Cache::add($this->hourKey($sedeId, now()), $score, self::SNAPSHOT_TTL);
    }

    private function computeTrend(int $sedeId, int $current): TrendDirection
    {
        $previous = Cache::get($this->hourKey($sedeId, now()->subHours(4)));

        if ($previous === null) {
            return TrendDirection::UNKNOWN;
        }

        $delta = $current - (int) $previous;

        if ($current < 50) {
            return TrendDirection::CRITICAL;
        }

        return match(true) {
            $delta >= 15 => TrendDirection::IMPROVING,
            $delta >= 5  => TrendDirection::RECOVERING,
            $delta <= -10 => TrendDirection::DETERIORATING,
            $delta <= -5  => TrendDirection::DETERIORATING,
            default      => TrendDirection::STABLE,
        };
    }

    private function hourKey(int $sedeId, Carbon $time): string
    {
        return "oi_snapshot_{$sedeId}_{$time->format('Y-m-d_H')}";
    }
}
