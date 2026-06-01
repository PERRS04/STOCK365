<?php

namespace App\OperationalIntelligence\Summaries;

use App\Enums\SignalSeverity;
use App\Enums\SignalType;
use App\Enums\SummaryType;
use App\Enums\TrendDirection;
use App\OperationalIntelligence\OperationalSignalEngine;
use App\OperationalIntelligence\Scoring\HealthScoreEngine;
use App\OperationalIntelligence\Trends\TrendAnalysisEngine;
use App\Values\OperationalSignal;
use App\Values\OperationalSummary;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class OperationalSummaryEngine
{
    private const CACHE_TTL = 60;

    public function __construct(
        private readonly OperationalSignalEngine $signalEngine,
        private readonly HealthScoreEngine       $scoreEngine,
        private readonly TrendAnalysisEngine     $trendEngine,
    ) {}

    // ─── Public API ─────────────────────────────────────────────────────────

    public function summarize(bool $fresh = false): Collection
    {
        if ($fresh) Cache::forget('oi_summaries');

        return Cache::remember('oi_summaries', self::CACHE_TTL, fn () => $this->generate());
    }

    public function systemDigest(): array
    {
        return Cache::remember('oi_digest', self::CACHE_TTL, fn () => $this->buildDigest());
    }

    public function flush(): void
    {
        Cache::forget('oi_summaries');
        Cache::forget('oi_digest');
    }

    // ─── Generation ─────────────────────────────────────────────────────────

    private function generate(): Collection
    {
        $raw     = $this->signalEngine->raw();
        $scores  = $this->scoreEngine->scoreAll()->keyBy('sede_id');
        $result  = collect();

        // Build summaries for sedes with active signals
        $bySedeId = $raw->filter(fn ($s) => $s->sedeId !== null)->groupBy('sedeId');

        foreach ($bySedeId as $sedeId => $sedeSignals) {
            $score = $scores[$sedeId] ?? null;
            $result->push($this->buildSedeSummary((int) $sedeId, $sedeSignals, $score));
        }

        // Positive summaries for healthy sedes with no signals
        foreach ($scores as $sedeId => $score) {
            if (!$bySedeId->has($sedeId) && $score['status'] === 'healthy') {
                $result->push($this->buildHealthySummary((int) $sedeId, $score));
            }
        }

        // Global signals (no sedeId)
        $global = $raw->filter(fn ($s) => $s->sedeId === null);
        if ($global->isNotEmpty()) {
            $result->push($this->buildGlobalSummary($global));
        }

        return $result->sortByDesc('priorityScore')->values();
    }

    private function buildSedeSummary(int $sedeId, Collection $signals, ?array $score): OperationalSummary
    {
        $sedeName = $signals->first()->sedeName ?? "Sede #{$sedeId}";

        $cashSignals = $signals->filter(fn ($s) => $s->type->category() === 'cash');
        $opSignals   = $signals->filter(fn ($s) => $s->type->category() === 'operational');
        $invSignals  = $signals->filter(fn ($s) => $s->type->category() === 'inventory');
        $oprSignals  = $signals->filter(fn ($s) => $s->type->category() === 'operator');

        $maxSeverity = $signals->sortByDesc(fn ($s) => $s->severity->value)->first()->severity;
        $trend       = $this->trendEngine->trendForSede($sedeId, $score['total'] ?? 50);
        $delta       = $this->trendEngine->deltaForSede($sedeId, $score['total'] ?? 50);

        $narrative      = $this->craftNarrative($sedeName, $signals, $cashSignals, $opSignals, $invSignals, $oprSignals, $score);
        $interpretation = $this->craftInterpretation($signals, $cashSignals, $opSignals, $invSignals, $oprSignals);
        $action         = $this->craftAction($maxSeverity, $cashSignals, $opSignals, $invSignals, $oprSignals);
        $type           = $this->pickType($cashSignals, $invSignals, $oprSignals, $opSignals);
        $priority       = $this->computePriority($signals, $score, $trend);

        return new OperationalSummary(
            id:                 "summary_sede_{$sedeId}",
            type:               $type,
            severity:           $maxSeverity,
            title:              $sedeName,
            narrative:          $narrative,
            interpretation:     $interpretation,
            suggestedAction:    $action,
            relatedSignalIds:   $signals->pluck('id')->all(),
            affectedSedeIds:    [$sedeId],
            affectedSedeNames:  [$sedeName],
            trend:              $trend,
            confidence:         round($signals->avg('confidence'), 2),
            generatedAt:        now(),
            context:            [
                'signal_count' => $signals->count(),
                'health_score' => $score['total'] ?? null,
                'sede_id'      => $sedeId,
                'delta'        => $delta,
            ],
            priorityScore:      $priority,
        );
    }

    private function buildHealthySummary(int $sedeId, array $score): OperationalSummary
    {
        $sedeName = $score['sede_name'];
        $trend    = $this->trendEngine->trendForSede($sedeId, $score['total']);
        $delta    = $this->trendEngine->deltaForSede($sedeId, $score['total']);

        $trendNote = match(true) {
            $delta >= 10  => " Mejoró {$delta} puntos en las últimas 4 horas.",
            $delta <= -5  => " Bajó " . abs($delta) . " puntos — mantener bajo vigilancia.",
            default       => "",
        };

        return new OperationalSummary(
            id:                 "summary_healthy_{$sedeId}",
            type:               SummaryType::POSITIVE_SIGNAL,
            severity:           SignalSeverity::INFO,
            title:              $sedeName,
            narrative:          "Operando con normalidad. Score {$score['total']}/100 — todos los indicadores en parámetros.{$trendNote}",
            interpretation:     "Flujo de caja, inventario y aprobaciones al día. Sin señales activas.",
            suggestedAction:    null,
            relatedSignalIds:   [],
            affectedSedeIds:    [$sedeId],
            affectedSedeNames:  [$sedeName],
            trend:              $trend,
            confidence:         1.0,
            generatedAt:        now(),
            context:            ['health_score' => $score['total'], 'delta' => $delta],
            priorityScore:      0,
        );
    }

    private function buildGlobalSummary(Collection $signals): OperationalSummary
    {
        $maxSev = $signals->sortByDesc(fn ($s) => $s->severity->value)->first()->severity;

        return new OperationalSummary(
            id:                 'summary_global',
            type:               SummaryType::OPERATIONAL_HEALTH,
            severity:           $maxSev,
            title:              'Sistema',
            narrative:          "{$signals->count()} señal(es) de sistema activas: " . $signals->map(fn ($s) => $s->title)->implode(', ') . '.',
            interpretation:     'Señales que no están vinculadas a una sede específica. Revisar logs del sistema.',
            suggestedAction:    'Revisar señales del sistema en el feed de inteligencia',
            relatedSignalIds:   $signals->pluck('id')->all(),
            affectedSedeIds:    [],
            affectedSedeNames:  [],
            trend:              TrendDirection::STABLE,
            confidence:         round($signals->avg('confidence'), 2),
            generatedAt:        now(),
            context:            ['signal_count' => $signals->count()],
            priorityScore:      $signals->filter(fn ($s) => $s->isCritical())->count() * 25,
        );
    }

    private function buildDigest(): array
    {
        $signals = $this->signalEngine->raw();
        $scores  = $this->scoreEngine->scoreAll();

        $critCount  = $signals->filter(fn ($s) => $s->isCritical())->count();
        $warnCount  = $signals->filter(fn ($s) => $s->severity === SignalSeverity::WARNING)->count();
        $avgHealth  = $scores->isEmpty() ? 100 : (int) round($scores->avg('total'));
        $riskySedes = $scores->filter(fn ($s) => $s['status'] !== 'healthy')->count();

        $state = match(true) {
            $critCount > 0                    => 'critical',
            $warnCount > 0 || $riskySedes > 0 => 'degraded',
            $avgHealth >= 80                  => 'optimal',
            default                           => 'normal',
        };

        $topSignal = $signals->sortByDesc(fn ($s) => $s->severity->value)->first();

        return [
            'state'          => $state,
            'state_label'    => match($state) {
                'critical' => 'CRÍTICO',
                'degraded' => 'BAJO MONITOREO',
                'optimal'  => 'ÓPTIMO',
                default    => 'NORMAL',
            },
            'state_color'    => match($state) {
                'critical' => 'red',
                'degraded' => 'amber',
                'optimal'  => 'emerald',
                default    => 'blue',
            },
            'top_concern'    => $topSignal?->title,
            'top_narrative'  => $topSignal?->body,
            'signal_count'   => $signals->count(),
            'critical_count' => $critCount,
            'warning_count'  => $warnCount,
            'avg_health'     => $avgHealth,
            'total_sedes'    => $scores->count(),
            'risky_sedes'    => $riskySedes,
        ];
    }

    // ─── Narrative Craft ────────────────────────────────────────────────────

    private function craftNarrative(
        string $sedeName,
        Collection $all,
        Collection $cash,
        Collection $ops,
        Collection $inv,
        Collection $opr,
        ?array $score
    ): string {
        // Pattern 1: Negative balance + pending deposits (most dangerous combination)
        $hasNegBal    = $cash->contains(fn ($s) => str_contains($s->id, 'cash_negative'));
        $hasDeposit   = $cash->contains(fn ($s) => $s->type === SignalType::DEPOSIT_DELAY);

        if ($hasNegBal && $hasDeposit) {
            $wait = $cash->filter(fn ($s) => $s->type === SignalType::DEPOSIT_DELAY)
                ->first()?->context['wait_minutes'] ?? '?';
            return "Balance negativo en caja con {$wait} min de movimientos sin aprobar. "
                 . "La combinación impide recuperar el saldo hasta que se procesen las aprobaciones.";
        }

        // Pattern 2: Long session + cash stall (possible abandonment)
        $hasLongSess  = $ops->contains(fn ($s) => $s->type === SignalType::OPERATIONAL_STALL);
        $hasCashStall = $cash->contains(fn ($s) => $s->type === SignalType::CASH_STALL);

        if ($hasLongSess && $hasCashStall) {
            $hours = $ops->filter(fn ($s) => $s->type === SignalType::OPERATIONAL_STALL)
                ->first()?->context['hours_open'] ?? '?';
            return "Sesión de {$hours}h sin actividad de ventas registrada. "
                 . "La combinación sugiere una posible interrupción operacional no reportada. "
                 . "Verificar presencia del operador.";
        }

        // Pattern 3: Low balance + approval bottleneck
        $hasLowBal    = $cash->contains(fn ($s) => str_contains($s->id, 'cash_low'));
        $hasApproval  = $ops->contains(fn ($s) => $s->type === SignalType::APPROVAL_BOTTLENECK);

        if ($hasLowBal && $hasApproval) {
            return "Saldo bajo con aprobaciones represadas — el flujo operacional está bloqueado. "
                 . "Aprobar los movimientos pendientes puede normalizar la posición de caja.";
        }

        // Pattern 4: Multiple inventory velocity issues
        $velItems  = $inv->filter(fn ($s) => $s->type === SignalType::INVENTORY_VELOCITY)->count();
        $critItems = $inv->filter(fn ($s) => $s->type === SignalType::INVENTORY_CRITICAL)->count();

        if ($velItems >= 2) {
            return "{$velItems} productos proyectados a agotarse hoy a la velocidad actual de ventas."
                 . ($critItems > 0 ? " {$critItems} producto(s) ya con stock agotado." : "")
                 . " Riesgo de quiebre que puede impactar el turno.";
        }

        if ($velItems === 1 && $critItems >= 1) {
            $product = $inv->filter(fn ($s) => $s->type === SignalType::INVENTORY_VELOCITY)->first()?->title ?? '';
            return "Presión crítica de inventario: {$product}. Stock agotado en {$critItems} producto(s) adicional(es).";
        }

        // Pattern 5: Multiple operator anomalies
        if ($opr->count() >= 2) {
            $oprName = $opr->first()?->operatorName ?? 'el operador';
            return "Múltiples señales de comportamiento atípico registradas para {$oprName}. "
                 . "Los patrones detectados requieren revisión del procedimiento operacional.";
        }

        // Pattern 6: All categories affected (full deterioration)
        $cats = collect([$cash->isNotEmpty(), $ops->isNotEmpty(), $inv->isNotEmpty(), $opr->isNotEmpty()])
            ->filter()->count();

        if ($cats >= 3) {
            return "Deterioro operacional en múltiples áreas: caja, inventario y flujo de aprobaciones presentan señales simultáneas. "
                 . "Requiere revisión integral de la sede.";
        }

        // Single dominant signal — use its body directly
        $dominant = $all->sortByDesc(fn ($s) => $s->severity->value)->first();
        $body     = $dominant?->body ?? '';
        $scoreNote = ($score && $score['total'] < 70) ? " Health: {$score['total']}/100." : '';

        return $body . $scoreNote;
    }

    private function craftInterpretation(
        Collection $all,
        Collection $cash,
        Collection $ops,
        Collection $inv,
        Collection $opr
    ): string {
        $hasCriticalCash = $cash->contains(fn ($s) => $s->isCritical());
        $hasCriticalOps  = $ops->contains(fn ($s) => $s->isCritical());
        $critInvCount    = $inv->filter(fn ($s) => $s->isCritical())->count();
        $totalCritical   = $all->filter(fn ($s) => $s->isCritical())->count();

        if ($hasCriticalCash) {
            return "Riesgo financiero directo — la posición de caja puede comprometerse si no se interviene.";
        }

        if ($hasCriticalOps) {
            return "Riesgo operacional elevado — una sesión no gestionada genera exposición financiera y auditora.";
        }

        if ($critInvCount >= 2) {
            return "Presión de inventario crítica — la rotura de stock en múltiples productos impacta las ventas del turno.";
        }

        if ($opr->isNotEmpty()) {
            return "Comportamiento atípico del operador puede indicar necesidad de entrenamiento o revisión de procedimientos.";
        }

        if ($totalCritical > 0) {
            return "La combinación de señales activas eleva el riesgo operacional total — cada pendiente suma presión.";
        }

        return "Situación bajo vigilancia — las señales no son críticas pero requieren atención para evitar escalada.";
    }

    private function craftAction(
        SignalSeverity $severity,
        Collection $cash,
        Collection $ops,
        Collection $inv,
        Collection $opr
    ): string {
        if ($severity === SignalSeverity::CRITICAL) {
            if ($cash->contains(fn ($s) => $s->isCritical())) {
                return "Revisar y aprobar movimientos de caja de inmediato";
            }
            if ($ops->contains(fn ($s) => $s->isCritical())) {
                return "Verificar estado del operador y considerar forzar cierre de sesión";
            }
            if ($inv->contains(fn ($s) => $s->isCritical())) {
                return "Ordenar reposición o transferencia de stock urgente";
            }
            return "Atención inmediata requerida — revisar estado de la sede";
        }

        if ($severity === SignalSeverity::WARNING) {
            if ($cash->isNotEmpty()) return "Revisar movimientos y balance de caja";
            if ($ops->isNotEmpty()) return "Resolver aprobaciones y monitorear sesión activa";
            if ($inv->isNotEmpty()) return "Programar reposición de inventario";
            return "Revisar el estado operacional de la sede";
        }

        return "Monitorear evolución — sin acción urgente requerida";
    }

    private function pickType(
        Collection $cash,
        Collection $inv,
        Collection $opr,
        Collection $ops
    ): SummaryType {
        if ($cash->contains(fn ($s) => $s->isCritical())) return SummaryType::CASHFLOW_RISK;
        if ($opr->isNotEmpty()) return SummaryType::OPERATOR_ANOMALY;
        if ($inv->isNotEmpty()) return SummaryType::INVENTORY_PRESSURE;
        if ($ops->isNotEmpty()) return SummaryType::APPROVAL_BOTTLENECK;
        if ($cash->isNotEmpty()) return SummaryType::CASHFLOW_RISK;

        return SummaryType::SEDE_RISK_PATTERN;
    }

    private function computePriority(Collection $signals, ?array $score, TrendDirection $trend): int
    {
        $base = 0;

        // Severity weight
        $base += $signals->filter(fn ($s) => $s->isCritical())->count() * 30;
        $base += $signals->filter(fn ($s) => $s->severity === SignalSeverity::WARNING)->count() * 15;
        $base += $signals->filter(fn ($s) => $s->severity === SignalSeverity::NOTICE)->count() * 5;

        // Health score impact
        if ($score) {
            $base += max(0, (70 - $score['total']));
        }

        // Cash signals are highest priority
        $cashCount = $signals->filter(fn ($s) => $s->type->category() === 'cash')->count();
        $base += $cashCount * 8;

        // Trend multiplier
        if ($trend === TrendDirection::DETERIORATING) $base = (int) ($base * 1.2);
        if ($trend === TrendDirection::CRITICAL)       $base = (int) ($base * 1.4);

        return min(100, $base);
    }
}
