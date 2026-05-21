<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryReceiptItem extends Model
{
    protected $fillable = ['receipt_id', 'product_id', 'cantidad', 'costo_unitario'];

    protected $casts = ['costo_unitario' => 'decimal:2'];

    public function receipt() { return $this->belongsTo(InventoryReceipt::class); }
    public function product() { return $this->belongsTo(Product::class); }

    public function subtotal(): float
    {
        return (float) $this->cantidad * (float) $this->costo_unitario;
    }
}
