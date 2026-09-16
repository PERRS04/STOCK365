<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_receipts', function (Blueprint $table) {
            // Drop existing FK before changing nullability (required by MySQL)
            $table->dropForeign(['sede_id']);

            // Allow NULL so boss can create receipts from POs without a sede.
            // The sede is chosen during approval via sede_id_override.
            $table->unsignedBigInteger('sede_id')->nullable()->change();

            // Re-add FK with RESTRICT — deleting a sede with linked receipts must be blocked
            $table->foreign('sede_id')->references('id')->on('sedes')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_receipts', function (Blueprint $table) {
            // WARNING: receipts with sede_id = NULL cannot be reverted safely in production.
            // This down() is intended for test/development environments with fresh data only.
            $table->dropForeign(['sede_id']);
            $table->unsignedBigInteger('sede_id')->nullable(false)->change();
            $table->foreign('sede_id')->references('id')->on('sedes')->onDelete('restrict');
        });
    }
};
