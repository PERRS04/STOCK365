<?php

namespace App\Values;

use App\Enums\RealtimeEventType;
use Carbon\Carbon;

/**
 * Immutable value object representing a normalized realtime operational event.
 * Built from activity_log rows by OperationalRealtimeService.
 *
 * Carries all presentation context so blade templates stay logic-free.
 */
readonly class RealtimeEvent
{
    public function __construct(
        public int               $id,
        public RealtimeEventType $type,
        public string            $title,
        public string            $description,
        public ?int              $sedeId,
        public ?string           $sedeName,
        public ?string           $operatorName,
        public Carbon            $timestamp,
        /** 'critical' | 'warning' | 'info' | 'low' */
        public string            $severity,
    ) {}

    public function isNew(Carbon $since): bool
    {
        return $this->timestamp->isAfter($since);
    }

    public function iconPath(): string
    {
        return $this->type->iconPath();
    }

    public function iconBg(): string
    {
        return $this->type->iconBg();
    }

    public function iconClr(): string
    {
        return $this->type->iconClr();
    }

    public function priorityBarClr(): string
    {
        return $this->type->priorityBarClr();
    }

    public function typeLabel(): string
    {
        return $this->type->label();
    }

    public function hasMeta(): bool
    {
        return $this->sedeName !== null || $this->operatorName !== null;
    }
}
