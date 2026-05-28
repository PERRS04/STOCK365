<?php

namespace App\Enums;

enum CashRiskLevel: string
{
    case NORMAL        = 'normal';
    case LOW_BALANCE   = 'low_balance';
    case NEGATIVE      = 'negative';
    case STALE_PENDING = 'stale_pending';
    case IDLE          = 'idle';

    public function label(): string
    {
        return match($this) {
            self::NORMAL        => 'Caja operativa',
            self::LOW_BALANCE   => 'Saldo bajo — verificar',
            self::NEGATIVE      => 'Déficit de caja — acción requerida',
            self::STALE_PENDING => 'Movimiento pendiente sin aprobar',
            self::IDLE          => 'Sin ventas recientes',
        };
    }

    public function statusClass(): string
    {
        return match($this) {
            self::NEGATIVE      => 'bg-red-50/70 border-red-100/90 text-red-800',
            self::LOW_BALANCE   => 'bg-amber-50/70 border-amber-100/80 text-amber-800',
            self::STALE_PENDING => 'bg-amber-50/70 border-amber-100/80 text-amber-800',
            self::IDLE          => 'bg-gray-50/80 border-gray-100 text-gray-500',
            self::NORMAL        => 'bg-emerald-50/60 border-emerald-100/70 text-emerald-800',
        };
    }

    public function dotClass(): string
    {
        return match($this) {
            self::NEGATIVE                      => 'bg-red-500',
            self::LOW_BALANCE, self::STALE_PENDING => 'bg-amber-400',
            self::IDLE                          => 'bg-gray-300',
            self::NORMAL                        => 'bg-emerald-500',
        };
    }

    public function ringClass(): string
    {
        return match($this) {
            self::NEGATIVE                      => 'bg-red-400',
            self::LOW_BALANCE, self::STALE_PENDING => 'bg-amber-400',
            self::NORMAL, self::IDLE            => 'bg-emerald-400',
        };
    }

    public function accentBar(): string
    {
        return match($this) {
            self::NEGATIVE      => 'bg-gradient-to-r from-red-500 via-red-600 to-rose-500',
            self::LOW_BALANCE,
            self::STALE_PENDING => 'bg-gradient-to-r from-amber-400 via-amber-500 to-orange-400',
            default             => 'bg-gradient-to-r from-emerald-400 via-emerald-500 to-teal-400',
        };
    }

    public function glowClass(): string
    {
        return match($this) {
            self::NEGATIVE      => 'glow-red border border-red-300/60',
            self::LOW_BALANCE,
            self::STALE_PENDING => 'glow-amber border border-amber-300/50',
            default             => 'glow-emerald border border-emerald-200/40',
        };
    }

    public function balanceClr(): string
    {
        return match($this) {
            self::NEGATIVE      => 'text-red-600',
            self::LOW_BALANCE,
            self::STALE_PENDING => 'text-amber-600',
            default             => 'text-gray-900',
        };
    }

    public function isNormal(): bool
    {
        return $this === self::NORMAL;
    }

    public function isCritical(): bool
    {
        return $this === self::NEGATIVE;
    }
}
