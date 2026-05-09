<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('sede_id')->constrained('sedes')->onDelete('cascade');
            $table->integer('stock_actual');
            $table->integer('stock_minimo');
            $table->boolean('alerta_activa')->default(true);
            $table->timestamp('fecha_alerta');
            $table->boolean('notificacion_enviada')->default(false);
            $table->timestamp('notificacion_enviada_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_alerts');
    }
};
