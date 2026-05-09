<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sede_id',
        'tipo',
        'cantidad',
        'motivo',
        'user_id',
        'observaciones',
        'fecha_movimiento',
    ];

    protected $casts = [
        'fecha_movimiento' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isEntry(): bool
    {
        return $this->tipo === 'entrada';
    }

    public function isExit(): bool
    {
        return $this->tipo === 'salida';
    }
}
