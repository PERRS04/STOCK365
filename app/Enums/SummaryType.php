<?php

namespace App\Enums;

enum SummaryType: string
{
    case SEDE_RISK_PATTERN   = 'sede_risk_pattern';
    case CASHFLOW_RISK       = 'cashflow_risk';
    case OPERATOR_ANOMALY    = 'operator_anomaly';
    case INVENTORY_PRESSURE  = 'inventory_pressure';
    case APPROVAL_BOTTLENECK = 'approval_bottleneck';
    case OPERATIONAL_HEALTH  = 'operational_health';
    case POSITIVE_SIGNAL     = 'positive_signal';
    case SYSTEM_OVERVIEW     = 'system_overview';

    public function label(): string
    {
        return match($this) {
            self::SEDE_RISK_PATTERN   => 'Riesgo de Sede',
            self::CASHFLOW_RISK       => 'Riesgo de Caja',
            self::OPERATOR_ANOMALY    => 'Anomalía Operador',
            self::INVENTORY_PRESSURE  => 'Presión de Inventario',
            self::APPROVAL_BOTTLENECK => 'Cuello de Botella',
            self::OPERATIONAL_HEALTH  => 'Salud Operacional',
            self::POSITIVE_SIGNAL     => 'Señal Positiva',
            self::SYSTEM_OVERVIEW     => 'Resumen del Sistema',
        };
    }

    public function isPositive(): bool
    {
        return $this === self::POSITIVE_SIGNAL;
    }
}
