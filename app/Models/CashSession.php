<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'sede_id',
        'opening_amount',
        'opened_at',
        'closed_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'opening_amount' => 'decimal:2',
        'opened_at'      => 'datetime',
        'closed_at'      => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function cashClosings()
    {
        return $this->hasMany(CashClosing::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────────────

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopePendingClosing($query)
    {
        return $query->where('status', 'pending_closing');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['open', 'pending_closing']);
    }

    // ── State helpers ─────────────────────────────────────────────────────────

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isPendingClosing(): bool
    {
        return $this->status === 'pending_closing';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getDurationAttribute(): string
    {
        $end   = $this->closed_at ?? now();
        $mins  = (int) $this->opened_at->diffInMinutes($end);
        $hours = intdiv($mins, 60);
        return sprintf('%02d:%02d', $hours, $mins % 60);
    }

    public function getTotalSalesAttribute(): float
    {
        return (float) $this->sales()
            ->where('estado', 'completada')
            ->sum('total_sistema');
    }

    public function getSalesCountAttribute(): int
    {
        return $this->sales()->where('estado', 'completada')->count();
    }

    // ── Static helpers ────────────────────────────────────────────────────────

    public static function activeForUser(int $userId, ?int $sedeId): ?self
    {
        if ($sedeId === null) {
            return null;
        }

        return static::where('user_id', $userId)
            ->where('sede_id', $sedeId)
            ->where('status', 'open')
            ->first();
    }
}
