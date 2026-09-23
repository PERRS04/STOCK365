<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSedePrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sede_id',
        'precio_venta',
        'activo',
    ];

    protected $casts = [
        'precio_venta' => 'decimal:2',
        'activo'       => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }
}
