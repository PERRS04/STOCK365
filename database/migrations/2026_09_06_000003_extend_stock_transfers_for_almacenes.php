<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            // Drop FKs on from/to sede so we can make them nullable
            $table->dropForeign(['from_sede_id']);
            $table->dropForeign(['to_sede_id']);

            // Make sede columns nullable (transfers between almacenes won't use sedes)
            $table->unsignedBigInteger('from_sede_id')->nullable()->change();
            $table->unsignedBigInteger('to_sede_id')->nullable()->change();

            // Re-add FKs with RESTRICT — transfer history must be preserved
            $table->foreign('from_sede_id')->references('id')->on('sedes')->onDelete('restrict');
            $table->foreign('to_sede_id')->references('id')->on('sedes')->onDelete('restrict');

            // almacen columns: nullable FKs with RESTRICT
            $table->foreignId('from_almacen_id')->nullable()->constrained('almacenes')->onDelete('restrict');
            $table->foreignId('to_almacen_id')->nullable()->constrained('almacenes')->onDelete('restrict');
        });

        // XOR on origin: transfer must come from exactly one type of location
        DB::statement(
            'ALTER TABLE stock_transfers ADD CONSTRAINT stock_transfers_from_xor
             CHECK (
                 (from_sede_id IS NOT NULL AND from_almacen_id IS NULL) OR
                 (from_sede_id IS NULL     AND from_almacen_id IS NOT NULL)
             )'
        );

        // XOR on destination: transfer must go to exactly one type of location
        DB::statement(
            'ALTER TABLE stock_transfers ADD CONSTRAINT stock_transfers_to_xor
             CHECK (
                 (to_sede_id IS NOT NULL AND to_almacen_id IS NULL) OR
                 (to_sede_id IS NULL     AND to_almacen_id IS NOT NULL)
             )'
        );
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE stock_transfers DROP CHECK stock_transfers_from_xor');
        DB::statement('ALTER TABLE stock_transfers DROP CHECK stock_transfers_to_xor');

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropForeign(['from_almacen_id']);
            $table->dropForeign(['to_almacen_id']);
            $table->dropColumn(['from_almacen_id', 'to_almacen_id']);

            // Restore from/to sede_id to NOT NULL
            $table->dropForeign(['from_sede_id']);
            $table->dropForeign(['to_sede_id']);
            $table->unsignedBigInteger('from_sede_id')->nullable(false)->change();
            $table->unsignedBigInteger('to_sede_id')->nullable(false)->change();
            $table->foreign('from_sede_id')->references('id')->on('sedes');
            $table->foreign('to_sede_id')->references('id')->on('sedes');
        });
    }
};
