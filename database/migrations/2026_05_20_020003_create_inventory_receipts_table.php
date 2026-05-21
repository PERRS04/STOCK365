<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained('sedes');
            $table->foreignId('user_id')->constrained('users');           // operator who registered it
            $table->string('supplier_name');
            $table->decimal('monto_pagado', 10, 2);
            $table->text('observaciones')->nullable();
            $table->string('invoice_path', 500)->nullable();              // uploaded file
            $table->enum('estado', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->foreignId('aprobado_por')->nullable()->constrained('users');
            $table->timestamp('aprobado_at')->nullable();
            $table->text('notas_aprobacion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_receipts');
    }
};
