<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sede_id',
        'cantidad_stock',
        'ultima_actualizacion',
    ];

    protected $casts = [
        'ultima_actualizacion' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function isLowStock(): bool
    {
        return $this->cantidad_stock < $this->product->stock_minimo;
    }

    public function isCritical(): bool
    {
        return $this->cantidad_stock < 5;
    }
}
