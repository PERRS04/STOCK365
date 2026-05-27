<?php

namespace App\Values;

use App\Enums\OperationalIssueType;
use App\Enums\OperationalStatus;
use App\Models\CashSession;

/**
 * Immutable value object representing a detected operational integrity issue.
 * Carry this through request lifecycle — never store in DB directly.
 */
readonly class OperationalIssue
{
    public function __construct(
        public OperationalIssueType $type,
        public OperationalStatus    $status,
        public CashSession          $session,
    ) {}

    public function message(): string
    {
        return $this->type->message();
    }

    public function resolution(): string
    {
        return $this->type->resolution();
    }

    public function severityLabel(): string
    {
        return $this->status->label();
    }

    public function color(): string
    {
        return $this->status->color();
    }

    public function isWarning(): bool
    {
        return $this->status === OperationalStatus::WARNING;
    }

    public function isCritical(): bool
    {
        return $this->status === OperationalStatus::CRITICAL
            || $this->status === OperationalStatus::LOCKED;
    }

    public function toArray(): array
    {
        return [
            'type'       => $this->type->value,
            'status'     => $this->status->value,
            'session_id' => $this->session->id,
        ];
    }
}
