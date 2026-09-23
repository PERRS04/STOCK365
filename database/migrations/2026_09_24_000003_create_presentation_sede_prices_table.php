<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presentation_sede_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presentation_id')
                  ->constrained('product_presentations')
                  ->onDelete('cascade');
            $table->foreignId('sede_id')
                  ->nullable()
                  ->constrained('sedes')
                  ->nullOnDelete();
            $table->decimal('precio_venta', 10, 2);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // NULL sede_id = global price — MySQL/SQLite allow multiple NULLs in a unique index
            $table->unique(['presentation_id', 'sede_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presentation_sede_prices');
    }
};
