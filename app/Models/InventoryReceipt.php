<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryReceipt extends Model
{
    protected $fillable = [
        'sede_id', 'user_id', 'provider_id', 'supplier_name', 'monto_pagado',
        'observaciones', 'invoice_path', 'estado',
        'aprobado_por', 'aprobado_at', 'notas_aprobacion',
    ];

    protected $casts = [
        'monto_pagado' => 'decimal:2',
        'aprobado_at'  => 'datetime',
    ];

    public function sede()       { return $this->belongsTo(Sede::class); }
    public function user()       { return $this->belongsTo(User::class); }
    public function provider()   { return $this->belongsTo(Provider::class); }
    public function approvedBy() { return $this->belongsTo(User::class, 'aprobado_por'); }
    public function items()      { return $this->hasMany(InventoryReceiptItem::class, 'receipt_id'); }

    public function isPending():  bool { return $this->estado === 'pendiente'; }
    public function isApproved(): bool { return $this->estado === 'aprobado'; }
    public function isRejected(): bool { return $this->estado === 'rechazado'; }
}
