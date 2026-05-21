<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre', 'ruc_nit', 'email', 'telefono',
        'contacto_principal', 'direccion', 'observaciones',
        'activo', 'created_by',
    ];

    protected $casts = ['activo' => 'boolean'];

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function receipts()
    {
        return $this->hasMany(InventoryReceipt::class, 'provider_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function totalCompras(): float
    {
        return (float) $this->receipts()->where('estado', 'aprobado')->sum('monto_pagado');
    }
}
