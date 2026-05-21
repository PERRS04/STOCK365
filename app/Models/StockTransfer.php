<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    protected $fillable = [
        'from_sede_id', 'to_sede_id', 'created_by', 'approved_by',
        'estado', 'motivo', 'notas_aprobacion', 'aprobado_at',
    ];

    protected $casts = ['aprobado_at' => 'datetime'];

    public function fromSede()  { return $this->belongsTo(Sede::class, 'from_sede_id'); }
    public function toSede()    { return $this->belongsTo(Sede::class, 'to_sede_id'); }
    public function createdBy() { return $this->belongsTo(User::class, 'created_by'); }
    public function approvedBy(){ return $this->belongsTo(User::class, 'approved_by'); }
    public function items()     { return $this->hasMany(StockTransferItem::class, 'transfer_id'); }

    public function isPending()  { return $this->estado === 'pendiente'; }
    public function isApproved() { return $this->estado === 'aprobado'; }
    public function isRejected() { return $this->estado === 'rechazado'; }
}
