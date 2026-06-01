<?php

namespace App\ExecutiveIntelligence;

class ExecutiveSummaryGenerator
{
    /**
     * Generates 5–7 executive sentences in Spanish.
     * Each sentence is self-contained and scoped to one concern.
     */
    public function generate(
        int    $avgHealth,
        int    $delta,
        string $trend,
        int    $criticalSedes,
        array  $topRisks,
        array  $topOpps,
        ?array $topPerformer,
        ?array $mostImproved,
        int    $inventoryAlerts,
        int    $pendingTotal,
    ): array {
        $sentences = [];

        // 1. Global health headline
        $deltaStr  = $delta >= 0 ? "+{$delta}" : "{$delta}";
        $trendEmoji = match($trend) {
            'improving' => '↑',
            'declining' => '↓',
            default     => '→',
        };
        $sentences[] = "Salud operacional global: {$avgHealth}/100 ({$deltaStr} pts, {$trendEmoji} {$this->trendLabel($trend)}).";

        // 2. Critical sede alert (only if there are critical sedes)
        if ($criticalSedes > 0) {
            $sedeWord = $criticalSedes === 1 ? 'sede requiere' : 'sedes requieren';
            $sentences[] = "{$criticalSedes} {$sedeWord} atención inmediata — salud por debajo del umbral crítico.";
        }

        // 3. Top risk (first one)
        if (!empty($topRisks[0])) {
            $risk = $topRisks[0];
            $sentences[] = "Riesgo principal: {$risk['sedeName']} — {$risk['message']}";
        }

        // 4. Operational pressure (pending approvals)
        if ($pendingTotal > 0) {
            $sentences[] = $this->pendingSentence($pendingTotal);
        }

        // 5. Inventory pressure (if significant)
        if ($inventoryAlerts >= 3) {
            $sentences[] = "{$inventoryAlerts} alertas de inventario activas — revisar reposición urgente.";
        } elseif ($inventoryAlerts > 0) {
            $sentences[] = "{$inventoryAlerts} alerta(s) de inventario activa(s) — sin urgencia inmediata.";
        }

        // 6. Workforce highlight
        if ($topPerformer) {
            $sentences[] = "Mejor operador de la semana: {$topPerformer['user_name']} con score {$topPerformer['score']}/100.";
        }

        if ($mostImproved) {
            $deltaImpr = $mostImproved['delta'] > 0 ? "+{$mostImproved['delta']}" : "{$mostImproved['delta']}";
            $sentences[] = "{$mostImproved['user_name']} lidera la mejora semanal ({$deltaImpr} pts vs. semana anterior).";
        }

        // 7. Opportunity highlight (if at least one opportunity)
        if (!empty($topOpps[0]) && $topOpps[0]['type'] !== 'workforce_excellence') {
            $opp = $topOpps[0];
            $sentences[] = "Oportunidad: {$opp['message']}";
        }

        // Fallback: all green sentence
        if (count($sentences) <= 2) {
            $sentences[] = "Todas las sedes operan dentro de parámetros normales — sin alertas prioritarias.";
        }

        return array_slice($sentences, 0, 7);
    }

    private function trendLabel(string $trend): string
    {
        return match($trend) {
            'improving' => 'mejorando',
            'declining' => 'en descenso',
            default     => 'estable',
        };
    }

    private function pendingSentence(int $total): string
    {
        if ($total >= 10) {
            return "{$total} aprobaciones pendientes acumuladas — flujo operacional bajo presión alta.";
        }
        if ($total >= 5) {
            return "{$total} aprobaciones pendientes — presión moderada en el flujo de operaciones.";
        }
        return "{$total} aprobación(es) pendiente(s) — sin impacto crítico por ahora.";
    }
}
