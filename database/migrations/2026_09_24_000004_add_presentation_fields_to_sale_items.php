<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->foreignId('presentation_id')
                  ->nullable()
                  ->after('product_id')
                  ->constrained('product_presentations')
                  ->restrictOnDelete();

            $table->string('presentation_name', 100)->nullable()->after('presentation_id');
            $table->unsignedInteger('presentation_factor')->nullable()->after('presentation_name');
            $table->unsignedInteger('cantidad_presentaciones')->nullable()->after('presentation_factor');
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('presentation_id');
            $table->dropColumn(['presentation_name', 'presentation_factor', 'cantidad_presentaciones']);
        });
    }
};
