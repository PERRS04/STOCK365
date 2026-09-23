<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPresentation extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'nombre',
        'factor_stock',
        'activo',
        'sort_order',
    ];

    protected $casts = [
        'activo'      => 'boolean',
        'factor_stock' => 'integer',
        'sort_order'   => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sedePrices()
    {
        return $this->hasMany(PresentationSedePrice::class, 'presentation_id');
    }
}
