<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index(['user_id', 'sede_id', 'created_at'], 'sales_user_sede_date_idx');
        });

        Schema::table('cash_closings', function (Blueprint $table) {
            $table->index(['user_id', 'sede_id', 'fecha_cierre'], 'cc_user_sede_date_idx');
        });

        Schema::table('cash_movements', function (Blueprint $table) {
            $table->index(['user_id', 'sede_id', 'type', 'created_at'], 'cm_user_sede_type_date_idx');
        });
    }

    public function down(): void
    {
        Schema::table('sales', fn (Blueprint $t) => $t->dropIndex('sales_user_sede_date_idx'));
        Schema::table('cash_closings', fn (Blueprint $t) => $t->dropIndex('cc_user_sede_date_idx'));
        Schema::table('cash_movements', fn (Blueprint $t) => $t->dropIndex('cm_user_sede_type_date_idx'));
    }
};
