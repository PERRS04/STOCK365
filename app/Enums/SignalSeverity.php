<?php

namespace App\Enums;

enum SignalSeverity: int
{
    case INFO     = 1;
    case NOTICE   = 2;
    case WARNING  = 3;
    case CRITICAL = 4;

    public function label(): string
    {
        return match($this) {
            self::INFO     => 'Info',
            self::NOTICE   => 'Aviso',
            self::WARNING  => 'Alerta',
            self::CRITICAL => 'Crítico',
        };
    }

    public function badgeClasses(): string
    {
        return match($this) {
            self::INFO     => 'bg-blue-50 text-blue-700 border border-blue-200/60',
            self::NOTICE   => 'bg-brand-50 text-brand-700 border border-brand-200/60',
            self::WARNING  => 'bg-amber-50 text-amber-700 border border-amber-200/60',
            self::CRITICAL => 'bg-red-50 text-red-700 border border-red-200/60',
        };
    }

    public function dotClass(): string
    {
        return match($this) {
            self::INFO     => 'bg-blue-400',
            self::NOTICE   => 'bg-brand-400',
            self::WARNING  => 'bg-amber-400',
            self::CRITICAL => 'bg-red-500',
        };
    }

    public function ringAnimated(): bool
    {
        return $this === self::CRITICAL;
    }

    public function cardBorderClass(): string
    {
        return match($this) {
            self::INFO     => 'border-blue-100/80',
            self::NOTICE   => 'border-brand-100/80',
            self::WARNING  => 'border-amber-200/60',
            self::CRITICAL => 'border-red-200/80',
        };
    }

    public function cardBgClass(): string
    {
        return match($this) {
            self::INFO     => 'bg-blue-50/30',
            self::NOTICE   => 'bg-brand-50/20',
            self::WARNING  => 'bg-amber-50/30',
            self::CRITICAL => 'bg-red-50/40',
        };
    }

    public function accentBarClass(): string
    {
        return match($this) {
            self::INFO     => 'bg-blue-400',
            self::NOTICE   => 'bg-brand-500',
            self::WARNING  => 'bg-amber-400',
            self::CRITICAL => 'bg-red-500',
        };
    }

    public function isCritical(): bool
    {
        return $this === self::CRITICAL;
    }

    public function isActionable(): bool
    {
        return $this->value >= 3;
    }
}
