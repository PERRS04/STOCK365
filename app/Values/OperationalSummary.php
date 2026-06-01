<?php

namespace App\Values;

use App\Enums\SignalSeverity;
use App\Enums\SummaryType;
use App\Enums\TrendDirection;
use Carbon\Carbon;

readonly class OperationalSummary
{
    public function __construct(
        public string         $id,
        public SummaryType    $type,
        public SignalSeverity $severity,
        public string         $title,
        public string         $narrative,
        public string         $interpretation,
        public ?string        $suggestedAction,
        public array          $relatedSignalIds,
        public array          $affectedSedeIds,
        public array          $affectedSedeNames,
        public TrendDirection $trend,
        public float          $confidence,
        public Carbon         $generatedAt,
        public array          $context,
        public int            $priorityScore,
    ) {}

    public function isCritical(): bool
    {
        return $this->severity === SignalSeverity::CRITICAL;
    }

    public function isPositive(): bool
    {
        return $this->type->isPositive();
    }

    public function isActionable(): bool
    {
        return $this->severity->isActionable() && !$this->isPositive();
    }

    public function confidencePct(): int
    {
        return (int) round($this->confidence * 100);
    }

    public function sedeLabel(): string
    {
        return implode(', ', $this->affectedSedeNames) ?: 'Sistema';
    }
}
