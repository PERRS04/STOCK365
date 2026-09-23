<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_receipts', function (Blueprint $table) {
            $table->foreignId('almacen_id')
                  ->nullable()
                  ->after('sede_id')
                  ->constrained('almacenes')
                  ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_receipts', function (Blueprint $table) {
            $table->dropForeign(['almacen_id']);
            $table->dropColumn('almacen_id');
        });
    }
};
