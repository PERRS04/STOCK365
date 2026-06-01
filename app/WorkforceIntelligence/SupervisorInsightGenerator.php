<?php

namespace App\WorkforceIntelligence;

use App\Models\WorkforceBehavioralPattern;
use App\Models\WorkforcePerformanceScore;
use Illuminate\Support\Collection;

class SupervisorInsightGenerator
{
    public function generateForUser(WorkforcePerformanceScore $score, Collection $patterns, ?array $delta): array
    {
        $insights = [];

        // Overall performance narrative
        $insights[] = $this->overallNarrative($score);

        // Score breakdown highlights
        foreach ($this->breakdownHighlights($score) as $highlight) {
            $insights[] = $highlight;
        }

        // Week-over-week trend
        if ($delta !== null) {
            $insights[] = $this->trendNarrative($delta, $score->user?->name);
        }

        // Active patterns
        foreach ($patterns->take(3) as $pattern) {
            $insights[] = $this->patternNarrative($pattern);
        }

        return $insights;
    }

    public function teamSummary(array $teamHealth, Collection $improvements): array
    {
        $insights = [];

        $avg = $teamHealth['avg'] ?? 0;
        $dist = $teamHealth['distribution'] ?? [];

        if ($avg >= 70) {
            $insights[] = [
                'type'    => 'positive',
                'message' => "El equipo mantiene un puntaje promedio de {$avg}/100. Rendimiento sólido general.",
            ];
        } elseif ($avg >= 55) {
            $insights[] = [
                'type'    => 'neutral',
                'message' => "Equipo en zona estable con promedio de {$avg}/100. Hay margen de mejora identificable.",
            ];
        } else {
            $insights[] = [
                'type'    => 'attention',
                'message' => "Promedio del equipo en {$avg}/100. Se recomienda revisar los factores de disciplina y calidad.",
            ];
        }

        if (($dist['coaching'] ?? 0) > 0) {
            $count = $dist['coaching'];
            $insights[] = [
                'type'    => 'coaching',
                'message' => "{$count} operador(es) en zona de coaching activo. Revisión individual recomendada.",
            ];
        }

        if (($dist['excellence'] ?? 0) > 0) {
            $count = $dist['excellence'];
            $insights[] = [
                'type'    => 'recognition',
                'message' => "{$count} operador(es) en nivel de Excelencia Operacional. Reconocimiento apropiado.",
            ];
        }

        if ($improvements->isNotEmpty()) {
            $topImproved = $improvements->first();
            $name  = $topImproved['user_name'] ?? 'Un operador';
            $delta = $topImproved['delta'] ?? 0;
            $insights[] = [
                'type'    => 'improvement',
                'message' => "{$name} mostró la mayor mejora esta semana (+{$delta} puntos). Reconocer el progreso.",
            ];
        }

        return $insights;
    }

    // ─── Narrative builders ──────────────────────────────────────────────────

    private function overallNarrative(WorkforcePerformanceScore $score): array
    {
        $name  = $score->user?->name ?? 'El operador';
        $s     = $score->total_score;
        $label = $score->performance_label;

        $message = match (true) {
            $s >= 85 => "{$name} está en Excelencia Operacional ({$s}/100). Desempeño consistente y confiable.",
            $s >= 70 => "{$name} mantiene rendimiento sólido ({$s}/100). Candidato a mayor responsabilidad.",
            $s >= 55 => "{$name} opera en zona estable ({$s}/100). Oportunidades de mejora identificadas.",
            $s >= 40 => "{$name} requiere atención ({$s}/100). Revisar factores con mayor impacto negativo.",
            default  => "{$name} necesita acompañamiento activo ({$s}/100). Sesión de coaching recomendada.",
        };

        return ['type' => 'overall', 'score' => $s, 'label' => $label, 'message' => $message];
    }

    private function breakdownHighlights(WorkforcePerformanceScore $score): array
    {
        $highlights = [];
        $name = $score->user?->name ?? 'El operador';

        if ($score->quality_score >= 90) {
            $highlights[] = ['type' => 'strength', 'message' => "Alta precisión en cajas — {$score->quality_score}/100 en calidad."];
        } elseif ($score->quality_score < 50) {
            $highlights[] = ['type' => 'weakness', 'message' => "Diferencias recurrentes en cierre. Calidad en {$score->quality_score}/100."];
        }

        if ($score->discipline_score >= 85) {
            $highlights[] = ['type' => 'strength', 'message' => "Excelente disciplina operativa — depósitos a tiempo y cierres correctos."];
        } elseif ($score->discipline_score < 50) {
            $highlights[] = ['type' => 'weakness', 'message' => "Disciplina por debajo del umbral ({$score->discipline_score}/100). Depósitos o cierres irregulares."];
        }

        if ($score->consistency_score >= 80) {
            $highlights[] = ['type' => 'strength', 'message' => "Rendimiento muy estable — baja variación día a día."];
        } elseif ($score->consistency_score < 45) {
            $highlights[] = ['type' => 'weakness', 'message' => "Alta variabilidad en rendimiento. Consistencia: {$score->consistency_score}/100."];
        }

        return $highlights;
    }

    private function trendNarrative(array $delta, ?string $name): array
    {
        $name ??= 'El operador';
        $d     = $delta['total_delta'];

        if ($d >= 10) {
            return ['type' => 'improvement', 'message' => "{$name} mejoró +{$d} puntos respecto a la semana anterior. Tendencia positiva."];
        } elseif ($d >= 3) {
            return ['type' => 'improvement', 'message' => "Leve mejora de +{$d} puntos en la última semana."];
        } elseif ($d <= -10) {
            return ['type' => 'attention', 'message' => "Descenso de {$d} puntos respecto a la semana pasada. Revisar qué cambió."];
        } elseif ($d <= -3) {
            return ['type' => 'neutral', 'message' => "Pequeño descenso de {$d} puntos esta semana. Monitorear."];
        }

        return ['type' => 'neutral', 'message' => "Rendimiento estable respecto a la semana anterior."];
    }

    private function patternNarrative(WorkforceBehavioralPattern $pattern): array
    {
        $ctx = $pattern->context ?? [];

        $message = match ($pattern->pattern_type) {
            'excessive_courtesies'     => "Promedio de {$ctx['avg_per_day']} cortesías/día en el período analizado. Revisar política.",
            'repeated_cash_differences' => "Diferencias en el {$ctx['diff_rate_pct']}% de cierres. Promedio: \${$ctx['avg_diff_amount']}.",
            'late_deposits'            => "El {$ctx['late_rate_pct']}% de los depósitos se realizan con 4+ horas de retraso.",
            'unclosed_sessions'        => "{$ctx['unclosed_count']} sesión(es) abiertas por más de 20 horas detectadas.",
            default                    => "Patrón detectado: {$pattern->pattern_type}.",
        };

        return [
            'type'     => 'pattern_' . $pattern->severity,
            'pattern'  => $pattern->pattern_type,
            'severity' => $pattern->severity,
            'message'  => $message,
            'occurrences' => $pattern->occurrence_count,
        ];
    }
}
