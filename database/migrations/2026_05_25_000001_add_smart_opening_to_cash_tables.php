<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Track which closing this session inherited its opening amount from
        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('inherited_from_closing_id')
                ->nullable()
                ->after('opening_amount');
        });

        // saldo_final = physical cash left in drawer at closing → becomes next day's opening
        // total_sistema will now store the full live balance (not just sales) for new closings
        Schema::table('cash_closings', function (Blueprint $table) {
            $table->decimal('saldo_final', 12, 2)->nullable()->default(0)->after('efectivo');
        });
    }

    public function down(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->dropColumn('inherited_from_closing_id');
        });

        Schema::table('cash_closings', function (Blueprint $table) {
            $table->dropColumn('saldo_final');
        });
    }
};
