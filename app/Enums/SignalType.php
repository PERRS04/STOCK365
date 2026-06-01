<?php

namespace App\Enums;

enum SignalType: string
{
    // ── Cash & Financial ────────────────────────────────────────────────────
    case CASH_RISK           = 'cash_risk';
    case CASH_STALL          = 'cash_stall';
    case DEPOSIT_DELAY       = 'deposit_delay';
    case CLOSING_ANOMALY     = 'closing_anomaly';

    // ── Operational ─────────────────────────────────────────────────────────
    case OPERATIONAL_STALL   = 'operational_stall';
    case APPROVAL_BOTTLENECK = 'approval_bottleneck';

    // ── Operator ────────────────────────────────────────────────────────────
    case OPERATOR_RISK       = 'operator_risk';
    case ANOMALOUS_ACTIVITY  = 'anomalous_activity';

    // ── Inventory ───────────────────────────────────────────────────────────
    case INVENTORY_CRITICAL  = 'inventory_critical';
    case INVENTORY_VELOCITY  = 'inventory_velocity';
    case INVENTORY_STAGNANT  = 'inventory_stagnant';

    // ── Predictive ──────────────────────────────────────────────────────────
    case PREDICTIVE_PRESSURE = 'predictive_pressure';

    public function label(): string
    {
        return match($this) {
            self::CASH_RISK            => 'Riesgo de Caja',
            self::CASH_STALL           => 'Caja Inactiva',
            self::DEPOSIT_DELAY        => 'Depósito Retrasado',
            self::CLOSING_ANOMALY      => 'Anomalía de Cierre',
            self::OPERATIONAL_STALL    => 'Estancamiento Operacional',
            self::APPROVAL_BOTTLENECK  => 'Cola de Aprobaciones',
            self::OPERATOR_RISK        => 'Riesgo de Operador',
            self::ANOMALOUS_ACTIVITY   => 'Actividad Anómala',
            self::INVENTORY_CRITICAL   => 'Stock Crítico',
            self::INVENTORY_VELOCITY   => 'Agotamiento Inminente',
            self::INVENTORY_STAGNANT   => 'Inventario Estancado',
            self::PREDICTIVE_PRESSURE  => 'Presión Predictiva',
        };
    }

    public function category(): string
    {
        return match($this) {
            self::CASH_RISK, self::CASH_STALL,
            self::DEPOSIT_DELAY, self::CLOSING_ANOMALY  => 'cash',
            self::OPERATIONAL_STALL,
            self::APPROVAL_BOTTLENECK                   => 'operational',
            self::OPERATOR_RISK,
            self::ANOMALOUS_ACTIVITY                    => 'operator',
            self::INVENTORY_CRITICAL, self::INVENTORY_VELOCITY,
            self::INVENTORY_STAGNANT                    => 'inventory',
            self::PREDICTIVE_PRESSURE                   => 'predictive',
        };
    }

    public function iconPath(): string
    {
        return match($this) {
            self::CASH_RISK, self::CASH_STALL           =>
                'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
            self::DEPOSIT_DELAY, self::CLOSING_ANOMALY  =>
                'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
            self::OPERATIONAL_STALL                     =>
                'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z',
            self::APPROVAL_BOTTLENECK                   =>
                'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
            self::OPERATOR_RISK, self::ANOMALOUS_ACTIVITY =>
                'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
            self::INVENTORY_CRITICAL, self::INVENTORY_VELOCITY, self::INVENTORY_STAGNANT =>
                'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
            self::PREDICTIVE_PRESSURE                   =>
                'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
        };
    }

    public function accentColor(): string
    {
        return match($this) {
            self::CASH_RISK, self::CLOSING_ANOMALY      => '#ef4444',
            self::CASH_STALL, self::OPERATIONAL_STALL   => '#f59e0b',
            self::DEPOSIT_DELAY, self::APPROVAL_BOTTLENECK => '#f59e0b',
            self::OPERATOR_RISK, self::ANOMALOUS_ACTIVITY  => '#8b5cf6',
            self::INVENTORY_CRITICAL                    => '#ef4444',
            self::INVENTORY_VELOCITY                    => '#f59e0b',
            self::INVENTORY_STAGNANT                    => '#9ca3af',
            self::PREDICTIVE_PRESSURE                   => '#3b82f6',
        };
    }
}
