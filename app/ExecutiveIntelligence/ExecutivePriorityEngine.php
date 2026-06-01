<?php

namespace App\ExecutiveIntelligence;

use App\Enums\SignalSeverity;
use Illuminate\Support\Collection;

class ExecutivePriorityEngine
{
    // ─── Risks ───────────────────────────────────────────────────────────────

    public function topRisks(Collection $signals, Collection $healthScores, array $sedeMap): array
    {
        $scored = $signals->map(function ($summary) use ($healthScores, $sedeMap) {
            $sedeId     = $summary->affectedSedeIds[0] ?? null;
            $health     = $sedeId ? ($healthScores->firstWhere('sede_id', $sedeId)['total'] ?? 50) : 50;
            $sedeStatus = $sedeId ? ($sedeMap[$sedeId]['status'] ?? 'unknown') : 'unknown';

            $score = $summary->priorityScore;

            // Boost: critical severity
            if ($summary->severity === SignalSeverity::CRITICAL) $score += 30;

            // Boost: sede already degraded
            if ($sedeStatus === 'critical') $score += 25;
            elseif ($sedeStatus === 'warning') $score += 10;

            // Boost: low health score amplifies risk
            $score += max(0, (60 - $health));

            // Boost: cashflow type is highest priority
            if ($summary->type->value === 'cashflow_risk') $score += 20;

            return [
                'priority'    => min(200, $score),
                'sedeId'      => $sedeId,
                'sedeName'    => $summary->affectedSedeNames[0] ?? 'Sistema',
                'type'        => $summary->type->value,
                'typeLabel'   => $summary->type->label(),
                'message'     => $summary->narrative,
                'severity'    => $summary->severity->value,
                'severityLabel' => $summary->severity->label(),
                'action'      => $summary->suggestedAction,
                'health'      => $health,
            ];
        });

        return $scored
            ->sortByDesc('priority')
            ->take(3)
            ->values()
            ->all();
    }

    // ─── Opportunities ───────────────────────────────────────────────────────

    public function topOpportunities(Collection $positives, Collection $allScores, Collection $healthScores): array
    {
        $opportunities = collect();

        // Source 1: Healthy sedes with good positive summaries
        foreach ($positives as $summary) {
            $sedeId = $summary->affectedSedeIds[0] ?? null;
            $health = $sedeId
                ? ($healthScores->firstWhere('sede_id', $sedeId)['total'] ?? 0)
                : 0;

            $score = $health; // base = health score

            // Improving trend amplifies the opportunity
            if ($summary->trend->value === 'improving') $score += 15;

            $opportunities->push([
                'priority'  => $score,
                'sedeId'    => $sedeId,
                'sedeName'  => $summary->affectedSedeNames[0] ?? 'Sistema',
                'type'      => 'healthy_operation',
                'message'   => $summary->narrative,
                'detail'    => $summary->interpretation,
                'health'    => $health,
            ]);
        }

        // Source 2: Top workforce performers (as coaching leverage opportunity)
        $topScore = $allScores->sortByDesc('total_score')->first();
        if ($topScore && $topScore->total_score >= 80) {
            $opportunities->push([
                'priority'  => (int) $topScore->total_score,
                'sedeId'    => $topScore->sede_id,
                'sedeName'  => $topScore->user?->sede?->nombre ?? 'Equipo',
                'type'      => 'workforce_excellence',
                'message'   => "Operador {$topScore->user?->name} con score {$topScore->total_score}/100 esta semana.",
                'detail'    => 'Aprovechar como referente para el resto del equipo.',
                'health'    => (int) $topScore->total_score,
            ]);
        }

        // Source 3: Improving sede (delta-based)
        $improvingSede = $healthScores
            ->filter(fn ($s) => ($s['status'] ?? '') === 'healthy')
            ->sortByDesc('total')
            ->first();

        if ($improvingSede && ($improvingSede['total'] ?? 0) >= 85) {
            $alreadyIn = $opportunities->contains(fn ($o) => $o['sedeId'] === $improvingSede['sede_id']);
            if (!$alreadyIn) {
                $opportunities->push([
                    'priority'  => (int) $improvingSede['total'],
                    'sedeId'    => $improvingSede['sede_id'],
                    'sedeName'  => $improvingSede['sede_name'],
                    'type'      => 'high_health_sede',
                    'message'   => "{$improvingSede['sede_name']} con salud operacional {$improvingSede['total']}/100.",
                    'detail'    => 'Sede en condiciones óptimas — buen momento para objetivos de ventas.',
                    'health'    => (int) $improvingSede['total'],
                ]);
            }
        }

        return $opportunities
            ->sortByDesc('priority')
            ->take(3)
            ->values()
            ->all();
    }
}
