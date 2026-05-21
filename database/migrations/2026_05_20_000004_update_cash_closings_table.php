<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_closings', function (Blueprint $table) {
            // Opening float (float de apertura) — cash in drawer at start of shift
            $table->decimal('monto_inicial', 10, 2)->default(0)->after('user_id');

            // Change from date to timestamp so we store exact closing time
            $table->timestamp('fecha_cierre')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('cash_closings', function (Blueprint $table) {
            $table->dropColumn('monto_inicial');
            $table->date('fecha_cierre')->change();
        });
    }
};
