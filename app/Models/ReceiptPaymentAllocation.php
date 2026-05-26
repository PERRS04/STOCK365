<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptPaymentAllocation extends Model
{
    protected $fillable = [
        'inventory_receipt_id',
        'source_type',
        'source_sede_id',
        'amount',
        'status',
        'evidence_path',
        'confirmed_by',
        'confirmed_at',
        'cash_movement_id',
        'notes',
    ];

    protected $casts = [
        'amount'       => 'decimal:2',
        'confirmed_at' => 'datetime',
    ];

    public function receipt()
    {
        return $this->belongsTo(InventoryReceipt::class, 'inventory_receipt_id');
    }

    public function sourceSede()
    {
        return $this->belongsTo(Sede::class, 'source_sede_id');
    }

    public function confirmedBy()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function cashMovement()
    {
        return $this->belongsTo(CashMovement::class);
    }

    public function isPending():   bool { return $this->status === 'pending'; }
    public function isConfirmed(): bool { return $this->status === 'confirmed'; }
}
