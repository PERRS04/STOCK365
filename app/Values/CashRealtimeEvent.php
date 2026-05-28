<?php

namespace App\Values;

use App\Enums\CashEventType;
use Carbon\Carbon;

/**
 * Immutable value object for a single cash session event.
 * Built by LiveCashEngineService — carries all presentation context.
 */
readonly class CashRealtimeEvent
{
    public function __construct(
        /** Stable identifier for wire:key — format: "{type}_{modelId}" */
        public string        $id,
        public CashEventType $type,
        public float         $amount,
        /** '+' | '-' | '' */
        public string        $direction,
        public string        $label,
        public ?string       $detail,
        public Carbon        $timestamp,
        /** 'completed' | 'pending' | 'rejected' */
        public string        $status,
        public bool          $isPending,
    ) {}

    public function barClr(): string   { return $this->type->barClr(); }
    public function iconBg(): string   { return $this->type->iconBg(); }
    public function iconClr(): string  { return $this->type->iconClr(); }
    public function iconPath(): string { return $this->type->iconPath(); }
    public function amountClr(): string { return $this->type->amountClr(); }

    /** Tailwind font-size class based on amount magnitude for visual weight. */
    public function amountSize(): string
    {
        return match(true) {
            $this->amount >= 500 => 'text-[20px]',
            $this->amount >= 100 => 'text-[16px]',
            default              => 'text-[13px]',
        };
    }

    public function isHighValue(): bool
    {
        return $this->amount >= 100;
    }

    public function formattedAmount(): string
    {
        if ($this->amount === 0.0 && $this->type === CashEventType::SESSION_OPEN) {
            return '$' . number_format(0, 2);
        }
        return ($this->direction ?: '') . '$' . number_format($this->amount, 2);
    }
}
