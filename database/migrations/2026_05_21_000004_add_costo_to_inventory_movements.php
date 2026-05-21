<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->decimal('costo_unitario', 10, 2)->nullable()->after('cantidad');
            $table->unsignedBigInteger('reference_id')->nullable()->after('costo_unitario');
            $table->string('reference_type', 50)->nullable()->after('reference_id');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropColumn(['costo_unitario', 'reference_id', 'reference_type']);
        });
    }
};
