<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', function (Blueprint $table) {
            // Drop existing FK so we can change sede_id to nullable
            $table->dropForeign(['sede_id']);

            // sede_id: nullable (almacen inventories won't have a sede)
            $table->unsignedBigInteger('sede_id')->nullable()->change();

            // Re-add FK with RESTRICT — never delete a sede with stock history
            $table->foreign('sede_id')->references('id')->on('sedes')->onDelete('restrict');

            // almacen_id: nullable FK with RESTRICT — deactivate, never delete
            $table->foreignId('almacen_id')->nullable()->constrained('almacenes')->onDelete('restrict');

            // Compound unique: one inventory record per (product, almacen)
            // MySQL allows multiple NULLs in UNIQUE indexes — sede rows unaffected
            $table->unique(['product_id', 'almacen_id']);
        });

        // XOR: exactly one location must be set — enforced at DB level
        DB::statement(
            'ALTER TABLE inventories ADD CONSTRAINT inventories_location_xor
             CHECK (
                 (sede_id IS NOT NULL AND almacen_id IS NULL) OR
                 (sede_id IS NULL     AND almacen_id IS NOT NULL)
             )'
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE inventories DROP CHECK inventories_location_xor');

        Schema::table('inventories', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'almacen_id']);
            $table->dropForeign(['almacen_id']);
            $table->dropColumn('almacen_id');

            // Restore sede_id to NOT NULL with original CASCADE
            $table->dropForeign(['sede_id']);
            $table->unsignedBigInteger('sede_id')->nullable(false)->change();
            $table->foreign('sede_id')->references('id')->on('sedes')->onDelete('cascade');
        });
    }
};
