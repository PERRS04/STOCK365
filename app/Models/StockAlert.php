<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sede_id',
        'stock_actual',
        'stock_minimo',
        'alerta_activa',
        'fecha_alerta',
        'notificacion_enviada',
        'notificacion_enviada_at',
    ];

    protected $casts = [
        'alerta_activa' => 'boolean',
        'notificacion_enviada' => 'boolean',
        'fecha_alerta' => 'datetime',
        'notificacion_enviada_at' => 'datetime',
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
