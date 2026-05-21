<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('sede_id')->constrained('sedes')->onDelete('cascade');
            $table->integer('cantidad_stock')->default(0);
            $table->timestamp('ultima_actualizacion')->useCurrent();
            $table->timestamps();
            $table->unique(['product_id', 'sede_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
