<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashClosing extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'sede_id',
        'user_id',
        'cash_session_id',
        'monto_inicial',
        'fecha_cierre',
        'total_sistema',
        'efectivo',
        'saldo_final',
        'transferencias',
        'cheques',
        'diferencia',
        'observaciones',
        'estado',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'fecha_cierre'  => 'datetime',
        'total_sistema' => 'decimal:2',
        'efectivo'      => 'decimal:2',
        'saldo_final'   => 'decimal:2',
        'transferencias' => 'decimal:2',
        'cheques'       => 'decimal:2',
        'diferencia'    => 'decimal:2',
        'approved_at'   => 'datetime',
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

    public function cashSession()
    {
        return $this->belongsTo(CashSession::class);
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
