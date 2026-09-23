<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id', 'product_id', 'cantidad',
        'precio_unitario', 'costo_unitario', 'subtotal',
        'presentation_id', 'presentation_name', 'presentation_factor', 'cantidad_presentaciones',
    ];

    protected $casts = [
        'precio_unitario'        => 'decimal:2',
        'costo_unitario'         => 'decimal:2',
        'subtotal'               => 'decimal:2',
        'presentation_factor'    => 'integer',
        'cantidad_presentaciones' => 'integer',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function presentation()
    {
        return $this->belongsTo(ProductPresentation::class, 'presentation_id');
    }
}
