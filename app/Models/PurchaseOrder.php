<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'created_by',
        'fecha_pedido',
        'fecha_estimada_entrega',
        'total',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_pedido' => 'date',
        'fecha_estimada_entrega' => 'date',
        'total' => 'decimal:2',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function isPending(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function isSent(): bool
    {
        return $this->estado === 'enviado';
    }

    public function isReceived(): bool
    {
        return $this->estado === 'recibido';
    }
}
