<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkforceBehavioralPattern extends Model
{
    protected $fillable = [
        'user_id', 'sede_id', 'pattern_type', 'severity',
        'occurrence_count', 'first_seen', 'last_seen', 'context', 'is_active',
    ];

    protected $casts = [
        'first_seen' => 'date',
        'last_seen' => 'date',
        'context' => 'array',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function getSeverityLabelAttribute(): string
    {
        return match ($this->severity) {
            'critical' => 'Crítico',
            'warning'  => 'Atención',
            default    => 'Informativo',
        };
    }
}
