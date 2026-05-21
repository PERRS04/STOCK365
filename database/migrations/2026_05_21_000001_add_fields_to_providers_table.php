<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->text('observaciones')->nullable()->after('direccion');
            $table->foreignId('created_by')->nullable()->constrained('users')->after('observaciones');
        });
    }

    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['observaciones', 'created_by']);
        });
    }
};
