<?php

namespace App\OperationalIntelligence;

use App\OperationalIntelligence\Aggregators\SignalAggregator;
use App\OperationalIntelligence\Detectors\ApprovalFlowDetector;
use App\OperationalIntelligence\Detectors\CashflowDetector;
use App\OperationalIntelligence\Detectors\InventoryDetector;
use App\OperationalIntelligence\Detectors\OperatorBehaviorDetector;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class OperationalSignalEngine
{
    private const CACHE_TTL      = 30;
    private const CACHE_KEY      = 'oi_signals_all';
    private const CACHE_KEY_RAW  = 'oi_signals_raw';

    public function __construct(
        private readonly CashflowDetector         $cashflow,
        private readonly OperatorBehaviorDetector  $behavior,
        private readonly InventoryDetector         $inventory,
        private readonly ApprovalFlowDetector      $approvals,
        private readonly SignalAggregator          $aggregator,
    ) {}

    public function detect(bool $fresh = false): Collection
    {
        if ($fresh) {
            Cache::forget(self::CACHE_KEY);
            Cache::forget(self::CACHE_KEY_RAW);
        }

        return Cache::remember(
            self::CACHE_KEY,
            self::CACHE_TTL,
            fn () => $this->aggregator->aggregate($this->raw())
        );
    }

    /**
     * Raw (unaggregated) signals — used by OperationalSummaryEngine for
     * richer narrative building before the digest grouping step.
     */
    public function raw(): Collection
    {
        return Cache::remember(self::CACHE_KEY_RAW, self::CACHE_TTL, fn () => collect()
            ->merge($this->cashflow->detect())
            ->merge($this->approvals->detect())
            ->merge($this->behavior->detect())
            ->merge($this->inventory->detect())
        );
    }

    public function detectForSede(int $sedeId): Collection
    {
        return $this->detect()->filter(fn ($s) => $s->sedeId === $sedeId)->values();
    }

    public function criticalCount(): int
    {
        return $this->detect()->filter(fn ($s) => $s->isCritical())->count();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY_RAW);
    }
}
