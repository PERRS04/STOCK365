<?php

namespace App\OperationalIntelligence\Context;

use App\Enums\SignalSeverity;
use App\OperationalIntelligence\OperationalSignalEngine;
use App\OperationalIntelligence\Scoring\HealthScoreEngine;
use App\OperationalIntelligence\Summaries\OperationalSummaryEngine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * OperationalContextBuilder
 *
 * Assembles a fully structured operational snapshot suitable for:
 * - Executive dashboards
 * - Future LLM integrations (Claude API)
 * - Operational audit exports
 *
 * When Claude API integration is added, call buildForClaude() and pass
 * the result as the user message alongside a system prompt.
 */
class OperationalContextBuilder
{
    private const CACHE_TTL = 60;
    private const CACHE_KEY = 'oi_context';

    public function __construct(
        private readonly OperationalSignalEngine  $signalEngine,
        private readonly HealthScoreEngine        $scoreEngine,
        private readonly OperationalSummaryEngine $summaryEngine,
    ) {}

    public function build(bool $fresh = false): array
    {
        if ($fresh) Cache::forget(self::CACHE_KEY);

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, fn () => $this->assemble());
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // ─── AI-Ready Context Format ─────────────────────────────────────────────

    /**
     * Returns a structured string prompt ready to be sent to Claude API.
     * Future use: pass to Anthropic\SDK\Client::messages()->create([...])
     */
    public function buildForClaude(): string
    {
        $ctx = $this->build();

        $sedeLines = collect($ctx['sedes'])->map(function ($sede) {
            $signals = collect($sede['active_signals'])->map(fn ($s) => "  • {$s['title']}")->implode("\n");
            $signalBlock = $signals ? "\n{$signals}" : " (sin señales)";
            return "- {$sede['name']}: health {$sede['health_score']}/100 [{$sede['health_status']}]{$signalBlock}";
        })->implode("\n");

        $concerns = collect($ctx['top_concerns'])->map(fn ($c) => "  → {$c}")->implode("\n");
        $positive = collect($ctx['positive_indicators'])->map(fn ($p) => "  ✓ {$p}")->implode("\n");

        return implode("\n", array_filter([
            "=== STOCK365 OPERATIONAL CONTEXT ===",
            "Fecha: " . $ctx['generated_at'],
            "Estado del sistema: " . strtoupper($ctx['operational_state']),
            "",
            "SEÑALES ACTIVAS:",
            "  Total: {$ctx['signals']['total']} | Críticas: {$ctx['signals']['critical']} | Alertas: {$ctx['signals']['warning']}",
            "",
            "SALUD POR SEDE:",
            $sedeLines,
            "",
            $concerns ? "PRINCIPALES PREOCUPACIONES:\n{$concerns}" : null,
            $positive ? "INDICADORES POSITIVOS:\n{$positive}" : null,
            "",
            "Health promedio: {$ctx['avg_health']}/100",
            "=================================",
        ]));
    }

    // ─── Internal ────────────────────────────────────────────────────────────

    private function assemble(): array
    {
        $signals   = $this->signalEngine->raw();
        $scores    = $this->scoreEngine->scoreAll();
        $summaries = $this->summaryEngine->summarize();
        $digest    = $this->summaryEngine->systemDigest();

        return [
            'generated_at'       => now()->toDateTimeString(),
            'operational_state'  => $digest['state'],
            'state_label'        => $digest['state_label'],
            'top_concern'        => $digest['top_concern'],
            'signals'            => [
                'total'    => $signals->count(),
                'critical' => $signals->filter(fn ($s) => $s->isCritical())->count(),
                'warning'  => $signals->filter(fn ($s) => $s->severity === SignalSeverity::WARNING)->count(),
                'notice'   => $signals->filter(fn ($s) => $s->severity === SignalSeverity::NOTICE)->count(),
            ],
            'avg_health'         => $scores->isEmpty() ? 100 : (int) round($scores->avg('total')),
            'sedes'              => $scores->map(fn ($score) => [
                'id'             => $score['sede_id'],
                'name'           => $score['sede_name'],
                'health_score'   => $score['total'],
                'health_status'  => $score['status'],
                'active_signals' => $signals
                    ->filter(fn ($s) => $s->sedeId === $score['sede_id'])
                    ->map(fn ($s) => [
                        'type'     => $s->type->value,
                        'title'    => $s->title,
                        'severity' => $s->severity->value,
                    ])
                    ->values()->all(),
            ])->values()->all(),
            'top_concerns'       => $summaries
                ->filter(fn ($s) => $s->isActionable())
                ->take(3)
                ->map(fn ($s) => $s->narrative)
                ->values()->all(),
            'positive_indicators' => $summaries
                ->filter(fn ($s) => $s->isPositive())
                ->take(3)
                ->map(fn ($s) => $s->narrative)
                ->values()->all(),
            'risky_sedes'        => $scores->filter(fn ($s) => $s['status'] !== 'healthy')->count(),
        ];
    }
}
