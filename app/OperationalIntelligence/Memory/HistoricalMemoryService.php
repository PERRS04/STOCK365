<?php

namespace App\OperationalIntelligence\Memory;

use App\Models\OperationalSnapshot;
use App\Models\Sede;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class HistoricalMemoryService
{
    private const CACHE_TTL = 300;

    // ─── Per-sede queries ────────────────────────────────────────────────────

    public function healthTrend(int $sedeId, int $hours = 24): Collection
    {
        return Cache::remember("hm_trend_{$sedeId}_{$hours}", self::CACHE_TTL, fn () =>
            OperationalSnapshot::forSede($sedeId)
                ->lastHours($hours)
                ->orderBy('captured_at')
                ->get(['captured_at', 'health_score', 'status', 'signal_count'])
        );
    }

    public function criticalHours(int $sedeId, int $hours = 24): int
    {
        return Cache::remember("hm_critical_{$sedeId}_{$hours}", self::CACHE_TTL, fn () =>
            OperationalSnapshot::forSede($sedeId)
                ->lastHours($hours)
                ->where('status', 'critical')
                ->count()
        );
    }

    public function averageHealthLast24h(int $sedeId): float
    {
        return Cache::remember("hm_avg24_{$sedeId}", self::CACHE_TTL, fn () =>
            (float) OperationalSnapshot::forSede($sedeId)
                ->lastHours(24)
                ->avg('health_score') ?? 0.0
        );
    }

    public function worstDay(int $sedeId, int $days = 7): ?array
    {
        return Cache::remember("hm_worst_{$sedeId}_{$days}", self::CACHE_TTL, function () use ($sedeId, $days) {
            $row = OperationalSnapshot::forSede($sedeId)
                ->lastDays($days)
                ->selectRaw('DATE(captured_at) as day, AVG(health_score) as avg_score, SUM(critical_signals) as total_critical')
                ->groupBy('day')
                ->orderBy('avg_score')
                ->first();

            return $row ? [
                'day'           => $row->day,
                'avg_score'     => round($row->avg_score, 1),
                'total_critical' => (int) $row->total_critical,
            ] : null;
        });
    }

    public function buildContextForSede(int $sedeId): array
    {
        return Cache::remember("hm_ctx_{$sedeId}", self::CACHE_TTL, function () use ($sedeId) {
            $trend      = $this->healthTrend($sedeId, 24);
            $critHours  = $this->criticalHours($sedeId, 24);
            $avg24      = $this->averageHealthLast24h($sedeId);
            $worstDay   = $this->worstDay($sedeId, 7);
            $last        = $trend->last();

            $direction = 'stable';
            if ($trend->count() >= 3) {
                $recent = $trend->take(-3)->avg('health_score');
                $older  = $trend->take(-6)->take(3)->avg('health_score') ?? $recent;
                if ($recent > $older + 5)      $direction = 'improving';
                elseif ($recent < $older - 5)  $direction = 'declining';
            }

            return [
                'sede_id'          => $sedeId,
                'avg_health_24h'   => $avg24,
                'critical_hours'   => $critHours,
                'trend_direction'  => $direction,
                'current_score'    => $last?->health_score ?? null,
                'current_status'   => $last?->status ?? null,
                'worst_day_7d'     => $worstDay,
                'snapshots_count'  => $trend->count(),
            ];
        });
    }

    // ─── Global context ──────────────────────────────────────────────────────

    public function buildGlobalContext(): array
    {
        return Cache::remember('hm_global_ctx', self::CACHE_TTL, function () {
            $sedes = Sede::where('activa', true)->pluck('id');

            $sedeContexts = $sedes->map(fn ($id) => $this->buildContextForSede($id))->values();

            $criticalSedes = $sedeContexts->filter(fn ($c) => ($c['current_status'] ?? '') === 'critical')->count();
            $warningSedes  = $sedeContexts->filter(fn ($c) => ($c['current_status'] ?? '') === 'warning')->count();
            $healthySedes  = $sedeContexts->filter(fn ($c) => ($c['current_status'] ?? '') === 'healthy')->count();
            $avgGlobal     = $sedeContexts->avg('avg_health_24h') ?? 0;

            return [
                'total_sedes'     => $sedes->count(),
                'healthy_sedes'   => $healthySedes,
                'warning_sedes'   => $warningSedes,
                'critical_sedes'  => $criticalSedes,
                'avg_health_24h'  => round($avgGlobal, 1),
                'sedes'           => $sedeContexts->all(),
            ];
        });
    }

    public function flushSede(int $sedeId): void
    {
        foreach (['trend', 'critical', 'avg24', 'worst', 'ctx'] as $key) {
            Cache::forget("hm_{$key}_{$sedeId}_24");
            Cache::forget("hm_{$key}_{$sedeId}_7");
            Cache::forget("hm_{$key}_{$sedeId}");
        }
    }
}
