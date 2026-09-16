<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_receipts', function (Blueprint $table) {
            $table->foreignId('purchase_order_id')
                  ->nullable()
                  ->after('sede_id')
                  ->constrained('purchase_orders')
                  ->nullOnDelete();
            // No UNIQUE — architecture supports PurchaseOrder 1 → N InventoryReceipt
            // for future partial receipts; uniqueness is enforced at application level.
        });
    }

    public function down(): void
    {
        Schema::table('inventory_receipts', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropColumn('purchase_order_id');
        });
    }
};
