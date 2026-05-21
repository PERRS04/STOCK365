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
        'costo_unitario',
        'reference_id',
        'reference_type',
    ];

    protected $casts = [
        'fecha_movimiento' => 'datetime',
        'costo_unitario'   => 'decimal:2',
        'reference_id'     => 'integer',
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

    public function isEntry(): bool       { return $this->tipo === 'entrada'; }
    public function isExit(): bool        { return $this->tipo === 'salida'; }
    public function isAdjustment(): bool  { return $this->tipo === 'ajuste'; }
    public function isLoss(): bool        { return $this->tipo === 'pérdida'; }
    public function isTransfer(): bool    { return $this->tipo === 'transferencia'; }

    public function isPositive(): bool    { return in_array($this->tipo, ['entrada', 'ajuste']); }
    public function isNegative(): bool    { return in_array($this->tipo, ['salida', 'pérdida', 'transferencia']); }

    public function tipoLabel(): string
    {
        return match($this->tipo) {
            'entrada'      => 'Entrada',
            'salida'       => 'Salida',
            'ajuste'       => 'Ajuste',
            'pérdida'      => 'Pérdida',
            'transferencia'=> 'Transferencia',
            default        => ucfirst($this->tipo),
        };
    }

    public function tipoColor(): string
    {
        return match($this->tipo) {
            'entrada'      => 'emerald',
            'salida'       => 'gray',
            'ajuste'       => 'blue',
            'pérdida'      => 'red',
            'transferencia'=> 'amber',
            default        => 'gray',
        };
    }
}
