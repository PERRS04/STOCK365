<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkforcePerformanceScore extends Model
{
    protected $fillable = [
        'user_id', 'sede_id', 'period_type', 'period_start', 'period_end',
        'productivity_score', 'total_sales', 'sales_volume', 'session_count',
        'quality_score', 'avg_cash_difference_pct', 'closings_count', 'exact_closings',
        'discipline_score', 'late_deposits', 'total_deposits', 'unclosed_sessions',
        'consistency_score', 'score_variance',
        'total_score', 'performance_label', 'computed_at',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'computed_at' => 'datetime',
        'sales_volume' => 'decimal:2',
        'avg_cash_difference_pct' => 'decimal:4',
        'score_variance' => 'decimal:4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function scopeForPeriod($query, string $type, string $start)
    {
        return $query->where('period_type', $type)->where('period_start', $start);
    }

    public function scopeForSede($query, int $sedeId)
    {
        return $query->where('sede_id', $sedeId);
    }

    public function getLabelColorAttribute(): string
    {
        return match (true) {
            $this->total_score >= 85 => 'emerald',
            $this->total_score >= 70 => 'blue',
            $this->total_score >= 55 => 'indigo',
            $this->total_score >= 40 => 'amber',
            default                  => 'red',
        };
    }
}
