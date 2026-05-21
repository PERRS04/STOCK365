<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    protected $fillable = ['transfer_id', 'product_id', 'cantidad'];

    public function transfer() { return $this->belongsTo(StockTransfer::class); }
    public function product()  { return $this->belongsTo(Product::class); }
}
