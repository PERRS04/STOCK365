<?php

namespace App\Values;

use App\Enums\OperationalAlertType;
use App\Enums\OperationalStatus;
use Carbon\Carbon;

/**
 * Immutable value object representing a detected operational alert
 * surfaced in the BossAlertCenter for admin/supervisor attention.
 *
 * Distinct from OperationalIssue (operator-facing lock screen) —
 * these alerts are for the command center, not the operator.
 */
readonly class OperationalAlert
{
    public function __construct(
        /** Deduplication key — same type + same subject = same id */
        public string               $id,
        public OperationalAlertType $type,
        public OperationalStatus    $severity,
        public string               $title,
        public string               $subtitle,
        public ?int                 $sedeId,
        public ?string              $sedeName,
        public ?int                 $operatorId,
        public ?string              $operatorName,
        public Carbon               $detectedAt,
        /** Type-specific data: session_id, closing_id, count, action routes, etc. */
        public array                $payload,
    ) {}

    public function isCritical(): bool
    {
        return $this->severity === OperationalStatus::CRITICAL;
    }

    public function isWarning(): bool
    {
        return $this->severity === OperationalStatus::WARNING;
    }

    public function hasForceClose(): bool
    {
        return isset($this->payload['session_id']) && in_array($this->type, [
            OperationalAlertType::ABANDONED_SESSION,
            OperationalAlertType::OPERATOR_ESCALATION,
        ]);
    }

    public function color(): string
    {
        return $this->severity->color(); // 'red' | 'amber' | 'emerald'
    }

    public function iconPath(): string
    {
        return $this->type->iconPath();
    }

    public function typeLabel(): string
    {
        return $this->type->label();
    }
}
