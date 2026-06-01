<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workforce_behavioral_patterns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sede_id')->constrained('sedes')->cascadeOnDelete();

            $table->string('pattern_type', 64);
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->unsignedSmallInteger('occurrence_count')->default(1);
            $table->date('first_seen');
            $table->date('last_seen');
            $table->json('context')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['user_id', 'pattern_type', 'is_active'], 'wbp_user_pattern_idx');
            $table->index(['sede_id', 'is_active', 'severity'], 'wbp_sede_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workforce_behavioral_patterns');
    }
};
