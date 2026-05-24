<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashMovement extends Model
{
    protected $fillable = [
        'sede_id', 'user_id', 'cash_session_id', 'type', 'amount',
        'motivo', 'observaciones', 'attachment_path',
        'status', 'approved_by', 'approved_at', 'rejection_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'amount'      => 'decimal:2',
    ];

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isPending(): bool
    {
        return $this->status === 'pendiente';
    }

    public function isApproved(): bool
    {
        return $this->status === 'aprobado';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rechazado';
    }

    public function isDebit(): bool
    {
        return in_array($this->type, ['retiro', 'pago_proveedor', 'envio_boveda', 'gasto_operativo']);
    }

    public function isCredit(): bool
    {
        return in_array($this->type, ['deposito', 'cambio']);
    }

    public static function typeLabel(string $type): string
    {
        return match($type) {
            'deposito'        => 'Depósito',
            'retiro'          => 'Retiro',
            'pago_proveedor'  => 'Pago Proveedor',
            'envio_boveda'    => 'Envío Bóveda',
            'cambio'          => 'Cambio/Fondo',
            'gasto_operativo' => 'Gasto Operativo',
            'ajuste'          => 'Ajuste',
            'diferencia'      => 'Diferencia',
            default           => $type,
        };
    }

    public static function typeColor(string $type): string
    {
        return match($type) {
            'deposito'        => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'cambio'          => 'bg-teal-50 text-teal-700 border-teal-200',
            'retiro'          => 'bg-red-50 text-red-700 border-red-200',
            'pago_proveedor'  => 'bg-amber-50 text-amber-700 border-amber-200',
            'envio_boveda'    => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'gasto_operativo' => 'bg-orange-50 text-orange-700 border-orange-200',
            'ajuste'          => 'bg-gray-100 text-gray-600 border-gray-200',
            'diferencia'      => 'bg-gray-100 text-gray-600 border-gray-200',
            default           => 'bg-gray-100 text-gray-600 border-gray-200',
        };
    }
}
