<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operational_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained('sedes')->cascadeOnDelete();
            $table->timestamp('captured_at');

            $table->unsignedTinyInteger('health_score')->default(0);
            $table->enum('status', ['healthy', 'warning', 'critical', 'locked'])->default('healthy');

            $table->unsignedTinyInteger('cash_score')->default(0);
            $table->unsignedTinyInteger('approval_score')->default(0);
            $table->unsignedTinyInteger('inventory_score')->default(0);
            $table->unsignedTinyInteger('operational_score')->default(0);

            $table->unsignedSmallInteger('signal_count')->default(0);
            $table->unsignedSmallInteger('critical_signals')->default(0);
            $table->unsignedSmallInteger('warning_signals')->default(0);

            $table->boolean('has_active_session')->default(false);
            $table->decimal('cash_total', 12, 2)->nullable();

            $table->timestamps();

            $table->index(['sede_id', 'captured_at']);
            $table->index('captured_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operational_snapshots');
    }
};
