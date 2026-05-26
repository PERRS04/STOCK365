<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashSessionAdjustment extends Model
{
    protected $fillable = [
        'cash_session_id',
        'sede_id',
        'adjusted_by',
        'monto_anterior',
        'monto_nuevo',
        'motivo',
    ];

    protected $casts = [
        'monto_anterior' => 'decimal:2',
        'monto_nuevo'    => 'decimal:2',
    ];

    public function cashSession()
    {
        return $this->belongsTo(CashSession::class);
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function adjustedBy()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }
}
