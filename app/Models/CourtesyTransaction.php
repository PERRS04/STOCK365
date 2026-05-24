<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtesyTransaction extends Model
{
    protected $fillable = [
        'sede_id', 'user_id', 'product_id', 'quantity', 'tipo',
        'motivo', 'cliente_nombre', 'observaciones', 'attachment_path',
        'status', 'approved_by', 'approved_at', 'rejection_reason',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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

    public static function tipoLabel(string $tipo): string
    {
        return match($tipo) {
            'cumpleaños'    => 'Cumpleaños',
            'apostador_vip' => 'Apostador VIP',
            'promocion'     => 'Promoción',
            'gerencia'      => 'Gerencia',
            'incidencia'    => 'Incidencia',
            'otro'          => 'Otro',
            default         => $tipo,
        };
    }

    public static function tipoColor(string $tipo): string
    {
        return match($tipo) {
            'cumpleaños'    => 'bg-pink-50 text-pink-700 border-pink-200',
            'apostador_vip' => 'bg-purple-50 text-purple-700 border-purple-200',
            'promocion'     => 'bg-blue-50 text-blue-700 border-blue-200',
            'gerencia'      => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'incidencia'    => 'bg-orange-50 text-orange-700 border-orange-200',
            'otro'          => 'bg-gray-100 text-gray-600 border-gray-200',
            default         => 'bg-gray-100 text-gray-600 border-gray-200',
        };
    }
}
