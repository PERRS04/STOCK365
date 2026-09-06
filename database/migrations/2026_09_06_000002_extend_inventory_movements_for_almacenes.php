<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            // Drop existing FK so we can make sede_id nullable
            $table->dropForeign(['sede_id']);

            // sede_id: nullable (almacen movements won't have a sede)
            $table->unsignedBigInteger('sede_id')->nullable()->change();

            // Re-add FK with RESTRICT — movement history must never be deleted
            $table->foreign('sede_id')->references('id')->on('sedes')->onDelete('restrict');

            // almacen_id: nullable FK with RESTRICT — same historical preservation rule
            $table->foreignId('almacen_id')->nullable()->constrained('almacenes')->onDelete('restrict');
            // No UNIQUE — movements are an append-only log, not a stock state table
        });

        // XOR: exactly one location per movement record
        DB::statement(
            'ALTER TABLE inventory_movements ADD CONSTRAINT inv_movements_location_xor
             CHECK (
                 (sede_id IS NOT NULL AND almacen_id IS NULL) OR
                 (sede_id IS NULL     AND almacen_id IS NOT NULL)
             )'
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE inventory_movements DROP CHECK inv_movements_location_xor');

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['almacen_id']);
            $table->dropColumn('almacen_id');

            // Restore sede_id to NOT NULL with original CASCADE
            $table->dropForeign(['sede_id']);
            $table->unsignedBigInteger('sede_id')->nullable(false)->change();
            $table->foreign('sede_id')->references('id')->on('sedes')->onDelete('cascade');
        });
    }
};
