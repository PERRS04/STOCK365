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
        'almacen_id',
        'cantidad_stock',
        'stock_recomendado',
        'ultima_actualizacion',
    ];

    protected $casts = [
        'ultima_actualizacion' => 'datetime',
        'stock_recomendado'    => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function locationKey(): string
    {
        return $this->sede_id
            ? "sede:{$this->sede_id}"
            : "almacen:{$this->almacen_id}";
    }

    public function isLowStock(): bool
    {
        return $this->cantidad_stock < ($this->product?->stock_minimo ?? 0);
    }

    public function isCritical(): bool
    {
        return $this->cantidad_stock < 5;
    }
}
