<?php

namespace App\Enums;

enum TrendDirection: string
{
    case IMPROVING     = 'improving';
    case RECOVERING    = 'recovering';
    case STABLE        = 'stable';
    case DETERIORATING = 'deteriorating';
    case CRITICAL      = 'critical';
    case UNKNOWN       = 'unknown';

    public function label(): string
    {
        return match($this) {
            self::IMPROVING     => 'Mejorando',
            self::RECOVERING    => 'Recuperando',
            self::STABLE        => 'Estable',
            self::DETERIORATING => 'Deteriorando',
            self::CRITICAL      => 'Crítico',
            self::UNKNOWN       => 'Sin datos',
        };
    }

    public function arrow(): string
    {
        return match($this) {
            self::IMPROVING     => '↑',
            self::RECOVERING    => '↗',
            self::STABLE        => '→',
            self::DETERIORATING => '↓',
            self::CRITICAL      => '↓',
            self::UNKNOWN       => '—',
        };
    }

    public function colorClass(): string
    {
        return match($this) {
            self::IMPROVING     => 'text-emerald-600',
            self::RECOVERING    => 'text-teal-600',
            self::STABLE        => 'text-gray-400',
            self::DETERIORATING => 'text-amber-600',
            self::CRITICAL      => 'text-red-600',
            self::UNKNOWN       => 'text-gray-300',
        };
    }

    public function bgClass(): string
    {
        return match($this) {
            self::IMPROVING     => 'bg-emerald-50 border-emerald-100',
            self::RECOVERING    => 'bg-teal-50 border-teal-100',
            self::STABLE        => 'bg-gray-50 border-gray-100',
            self::DETERIORATING => 'bg-amber-50 border-amber-200/60',
            self::CRITICAL      => 'bg-red-50 border-red-200/60',
            self::UNKNOWN       => 'bg-gray-50 border-gray-100',
        };
    }

    public function isNegative(): bool
    {
        return in_array($this, [self::DETERIORATING, self::CRITICAL]);
    }
}
