<?php

namespace App\Values;

use App\Enums\SignalSeverity;
use App\Enums\SignalType;
use Carbon\Carbon;

readonly class OperationalSignal
{
    public function __construct(
        public string         $id,
        public SignalType     $type,
        public SignalSeverity $severity,
        public string         $title,
        public string         $body,
        public ?int           $sedeId,
        public ?string        $sedeName,
        public ?int           $operatorId,
        public ?string        $operatorName,
        public Carbon         $detectedAt,
        public float          $confidence,
        public ?string        $suggestedAction,
        public array          $context,
        public bool           $isPredictive,
        public bool           $isDigest = false,
    ) {}

    public function isCritical(): bool
    {
        return $this->severity === SignalSeverity::CRITICAL;
    }

    public function isActionable(): bool
    {
        return $this->severity->isActionable();
    }

    public function confidencePct(): int
    {
        return (int) round($this->confidence * 100);
    }

    public function ageInMinutes(): int
    {
        return (int) $this->detectedAt->diffInMinutes(now());
    }

    public function typeLabel(): string
    {
        return $this->type->label();
    }

    public function sedeLabel(): string
    {
        return $this->sedeName ?? 'Sistema';
    }

    public function digestSignals(): array
    {
        return $this->context['signals'] ?? [];
    }
}
