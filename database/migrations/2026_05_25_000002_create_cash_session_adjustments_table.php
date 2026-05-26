<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_session_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cash_session_id');
            $table->unsignedBigInteger('sede_id');
            $table->unsignedBigInteger('adjusted_by');
            $table->decimal('monto_anterior', 12, 2);
            $table->decimal('monto_nuevo', 12, 2);
            $table->string('motivo', 500);
            $table->timestamps();

            $table->foreign('cash_session_id')->references('id')->on('cash_sessions')->cascadeOnDelete();
            $table->foreign('sede_id')->references('id')->on('sedes')->cascadeOnDelete();
            $table->foreign('adjusted_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_session_adjustments');
    }
};
