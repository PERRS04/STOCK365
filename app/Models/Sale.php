<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'sede_id',
        'user_id',
        'total_sistema',
        'descuento',
        'estado',
        'fecha_venta',
    ];

    protected $casts = [
        'total_sistema' => 'decimal:2',
        'descuento' => 'decimal:2',
        'fecha_venta' => 'datetime',
    ];

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function getTotalNetAttribute()
    {
        return $this->total_sistema - $this->descuento;
    }
}
