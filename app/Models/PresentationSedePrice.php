<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresentationSedePrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'presentation_id',
        'sede_id',
        'precio_venta',
        'activo',
    ];

    protected $casts = [
        'precio_venta' => 'decimal:2',
        'activo'       => 'boolean',
    ];

    public function presentation()
    {
        return $this->belongsTo(ProductPresentation::class, 'presentation_id');
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }
}
