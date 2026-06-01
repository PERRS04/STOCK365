<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workforce_performance_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sede_id')->constrained('sedes')->cascadeOnDelete();
            $table->enum('period_type', ['daily', 'weekly', 'monthly']);
            $table->date('period_start');
            $table->date('period_end');

            $table->unsignedTinyInteger('productivity_score')->default(0);
            $table->unsignedInteger('total_sales')->default(0);
            $table->decimal('sales_volume', 12, 2)->default(0);
            $table->unsignedInteger('session_count')->default(0);

            $table->unsignedTinyInteger('quality_score')->default(0);
            $table->decimal('avg_cash_difference_pct', 8, 4)->default(0);
            $table->unsignedSmallInteger('closings_count')->default(0);
            $table->unsignedSmallInteger('exact_closings')->default(0);

            $table->unsignedTinyInteger('discipline_score')->default(0);
            $table->unsignedSmallInteger('late_deposits')->default(0);
            $table->unsignedSmallInteger('total_deposits')->default(0);
            $table->unsignedSmallInteger('unclosed_sessions')->default(0);

            $table->unsignedTinyInteger('consistency_score')->default(0);
            $table->decimal('score_variance', 8, 4)->default(0);

            $table->unsignedTinyInteger('total_score')->default(0);
            $table->string('performance_label', 64)->default('Solid');
            $table->timestamp('computed_at')->useCurrent();

            $table->timestamps();

            $table->unique(['user_id', 'period_type', 'period_start'], 'wps_unique_period');
            $table->index(['period_type', 'period_start', 'total_score'], 'wps_period_score_idx');
            $table->index(['sede_id', 'period_type', 'period_start'], 'wps_sede_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workforce_performance_scores');
    }
};
