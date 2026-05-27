<?php

namespace App\Enums;

enum OperationalStatus: string
{
    case HEALTHY  = 'healthy';
    case WARNING  = 'warning';
    case CRITICAL = 'critical';
    case LOCKED   = 'locked';

    public function label(): string
    {
        return match($this) {
            self::HEALTHY  => 'Operacional',
            self::WARNING  => 'Advertencia',
            self::CRITICAL => 'Crítico',
            self::LOCKED   => 'Bloqueado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::HEALTHY  => 'emerald',
            self::WARNING  => 'amber',
            self::CRITICAL => 'red',
            self::LOCKED   => 'slate',
        };
    }

    public function isBlocking(): bool
    {
        return $this !== self::HEALTHY;
    }
}
