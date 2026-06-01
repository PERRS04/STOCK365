<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationalSnapshot extends Model
{
    protected $fillable = [
        'sede_id', 'captured_at', 'health_score', 'status',
        'cash_score', 'approval_score', 'inventory_score', 'operational_score',
        'signal_count', 'critical_signals', 'warning_signals',
        'has_active_session', 'cash_total',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'has_active_session' => 'boolean',
        'cash_total' => 'decimal:2',
    ];

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function scopeForSede($query, int $sedeId)
    {
        return $query->where('sede_id', $sedeId);
    }

    public function scopeLastDays($query, int $days)
    {
        return $query->where('captured_at', '>=', now()->subDays($days));
    }

    public function scopeLastHours($query, int $hours)
    {
        return $query->where('captured_at', '>=', now()->subHours($hours));
    }
}
