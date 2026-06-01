<?php

namespace App\OperationalIntelligence\Memory;

use App\Models\OperationalSnapshot;
use Illuminate\Support\Collection;

class PatternDetectionEngine
{
    public function detectForSede(int $sedeId, int $days = 14): array
    {
        $snapshots = OperationalSnapshot::forSede($sedeId)
            ->lastDays($days)
            ->orderBy('captured_at')
            ->get();

        if ($snapshots->isEmpty()) {
            return [];
        }

        return array_filter([
            $this->detectConsistentlyCritical($snapshots),
            $this->detectMorningWeakness($snapshots),
            $this->detectEndOfDayDecline($snapshots),
            $this->detectFrequentSignalSpikes($snapshots),
            $this->detectNoSessionGaps($snapshots),
        ]);
    }

    private function detectConsistentlyCritical(Collection $snapshots): ?array
    {
        $criticalPct = $snapshots->filter(fn ($s) => $s->status === 'critical')->count() / $snapshots->count();

        if ($criticalPct >= 0.40) {
            return [
                'type'        => 'consistently_critical',
                'severity'    => 'critical',
                'description' => 'Más del 40% del tiempo operativo en estado crítico.',
                'detail'      => ['critical_pct' => round($criticalPct * 100, 1)],
            ];
        }

        return null;
    }

    private function detectMorningWeakness(Collection $snapshots): ?array
    {
        $morning = $snapshots->filter(fn ($s) => in_array((int) $s->captured_at->format('G'), [7, 8, 9, 10]));

        if ($morning->count() < 5) return null;

        $avgMorning  = $morning->avg('health_score');
        $avgOverall  = $snapshots->avg('health_score');

        if ($avgMorning < $avgOverall - 15) {
            return [
                'type'        => 'morning_weakness',
                'severity'    => 'warning',
                'description' => 'Puntaje de salud consistentemente bajo en las mañanas (7–10h).',
                'detail'      => [
                    'avg_morning' => round($avgMorning, 1),
                    'avg_overall' => round($avgOverall, 1),
                ],
            ];
        }

        return null;
    }

    private function detectEndOfDayDecline(Collection $snapshots): ?array
    {
        $evening = $snapshots->filter(fn ($s) => in_array((int) $s->captured_at->format('G'), [17, 18, 19, 20]));

        if ($evening->count() < 5) return null;

        $avgEvening = $evening->avg('health_score');
        $avgOverall = $snapshots->avg('health_score');

        if ($avgEvening < $avgOverall - 12) {
            return [
                'type'        => 'end_of_day_decline',
                'severity'    => 'warning',
                'description' => 'Deterioro operativo recurrente en cierre de turno (17–20h).',
                'detail'      => [
                    'avg_evening' => round($avgEvening, 1),
                    'avg_overall' => round($avgOverall, 1),
                ],
            ];
        }

        return null;
    }

    private function detectFrequentSignalSpikes(Collection $snapshots): ?array
    {
        $spikes = $snapshots->filter(fn ($s) => $s->critical_signals >= 3)->count();

        if ($spikes >= 4) {
            return [
                'type'        => 'frequent_signal_spikes',
                'severity'    => 'warning',
                'description' => "Se detectaron {$spikes} episodios con 3+ señales críticas simultáneas.",
                'detail'      => ['spike_count' => $spikes],
            ];
        }

        return null;
    }

    private function detectNoSessionGaps(Collection $snapshots): ?array
    {
        $gaps = $snapshots
            ->filter(fn ($s) => !$s->has_active_session)
            ->groupBy(fn ($s) => $s->captured_at->format('Y-m-d'))
            ->filter(fn ($day) => $day->count() >= 6)
            ->count();

        if ($gaps >= 3) {
            return [
                'type'        => 'session_gaps',
                'severity'    => 'info',
                'description' => "{$gaps} días sin sesión activa por 6+ horas consecutivas.",
                'detail'      => ['days_with_gaps' => $gaps],
            ];
        }

        return null;
    }
}
