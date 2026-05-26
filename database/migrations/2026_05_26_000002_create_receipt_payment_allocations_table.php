<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_receipt_id')
                  ->constrained('inventory_receipts')
                  ->cascadeOnDelete();
            $table->enum('source_type', ['local_cash', 'other_branch', 'boss'])
                  ->default('local_cash');
            $table->foreignId('source_sede_id')
                  ->nullable()
                  ->constrained('sedes')
                  ->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'confirmed'])->default('pending');
            $table->string('evidence_path')->nullable();
            $table->foreignId('confirmed_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('cash_movement_id')
                  ->nullable()
                  ->constrained('cash_movements')
                  ->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_payment_allocations');
    }
};
