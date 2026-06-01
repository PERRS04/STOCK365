<?php

namespace App\OperationalIntelligence\Aggregators;

use App\Enums\SignalSeverity;
use App\Enums\SignalType;
use App\Values\OperationalSignal;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SignalAggregator
{
    private const DIGEST_THRESHOLD = 3;

    /**
     * Aggregate signals: create a narrative digest for sedes with >= 3 signals,
     * while passing through individual signals for sedes with fewer issues.
     */
    public function aggregate(Collection $signals): Collection
    {
        $bySedeId = $signals->filter(fn ($s) => $s->sedeId !== null)->groupBy('sedeId');
        $noSede   = $signals->filter(fn ($s) => $s->sedeId === null);

        $result = collect();

        foreach ($bySedeId as $sedeId => $sedeSignals) {
            if ($sedeSignals->count() < self::DIGEST_THRESHOLD) {
                $result = $result->merge($sedeSignals);
                continue;
            }

            // Build a narrative digest for this sede
            $result->push($this->buildDigest($sedeId, $sedeSignals));
        }

        return $result
            ->merge($noSede)
            ->sortByDesc(fn ($s) => $s->severity->value)
            ->values();
    }

    private function buildDigest(int $sedeId, Collection $sedeSignals): OperationalSignal
    {
        $sedeName    = $sedeSignals->first()->sedeName ?? "Sede #{$sedeId}";
        $maxSeverity = $sedeSignals->sortByDesc(fn ($s) => $s->severity->value)->first()->severity;
        $avgConf     = round($sedeSignals->avg('confidence'), 2);

        $signalList = $sedeSignals
            ->sortByDesc(fn ($s) => $s->severity->value)
            ->map(fn ($s) => ['title' => $s->title, 'type' => $s->type->value, 'severity' => $s->severity->value])
            ->values()
            ->all();

        $bulletLines = $sedeSignals
            ->sortByDesc(fn ($s) => $s->severity->value)
            ->map(fn ($s) => "• {$s->title}")
            ->implode("\n");

        return new OperationalSignal(
            id:              "digest_sede_{$sedeId}",
            type:            SignalType::PREDICTIVE_PRESSURE,
            severity:        $maxSeverity,
            title:           "{$sedeName} · {$sedeSignals->count()} señales activas",
            body:            $bulletLines,
            sedeId:          $sedeId,
            sedeName:        $sedeName,
            operatorId:      null,
            operatorName:    null,
            detectedAt:      now(),
            confidence:      $avgConf,
            suggestedAction: 'Revisar sede completa — múltiples señales detectadas',
            context:         [
                'is_digest'    => true,
                'signal_count' => $sedeSignals->count(),
                'signals'      => $signalList,
            ],
            isPredictive:    false,
            isDigest:        true,
        );
    }
}
