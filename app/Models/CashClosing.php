<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashClosing extends Model
{
    use HasFactory;

    protected $fillable = [
        'sede_id',
        'user_id',
        'fecha_cierre',
        'total_sistema',
        'efectivo',
        'transferencias',
        'cheques',
        'diferencia',
        'observaciones',
        'estado',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'fecha_cierre' => 'date',
        'total_sistema' => 'decimal:2',
        'efectivo' => 'decimal:2',
        'transferencias' => 'decimal:2',
        'cheques' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isBalanced(): bool
    {
        return abs($this->diferencia) < 0.01; // tolerancia de centavos
    }

    public function isPending(): bool
    {
        return $this->estado === 'pendiente';
    }
}
