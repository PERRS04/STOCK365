<?php

namespace App\Enums;

enum IncidentStatus: string
{
    case OPEN         = 'open';
    case ACKNOWLEDGED = 'acknowledged';
    case IN_PROGRESS  = 'in_progress';
    case RESOLVED     = 'resolved';
    case IGNORED      = 'ignored';

    public function label(): string
    {
        return match($this) {
            self::OPEN         => 'Sin atender',
            self::ACKNOWLEDGED => 'Visto',
            self::IN_PROGRESS  => 'En progreso',
            self::RESOLVED     => 'Resuelto',
            self::IGNORED      => 'Ignorado',
        };
    }

    public function chipClasses(): string
    {
        return match($this) {
            self::OPEN         => 'bg-gray-100 text-gray-500 border-gray-200',
            self::ACKNOWLEDGED => 'bg-blue-50 text-blue-600 border-blue-200',
            self::IN_PROGRESS  => 'bg-amber-50 text-amber-700 border-amber-200',
            self::RESOLVED     => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::IGNORED      => 'bg-gray-50 text-gray-400 border-gray-100',
        };
    }

    public function isTerminal(): bool
    {
        return $this === self::RESOLVED || $this === self::IGNORED;
    }
}
