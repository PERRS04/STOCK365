<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $realProviders = ['Cervecería Nacional', 'Señor Crespo', 'Punto de Chelo'];

        $demoIds = DB::table('providers')
            ->whereNotIn('nombre', $realProviders)
            ->pluck('id');

        if ($demoIds->isNotEmpty()) {
            // Preserve receipt history: unlink from demo providers (supplier_name still stored)
            DB::table('inventory_receipts')
                ->whereIn('provider_id', $demoIds)
                ->update(['provider_id' => null]);

            DB::table('providers')
                ->whereIn('id', $demoIds)
                ->delete();
        }
    }

    public function down(): void
    {
        // Non-reversible data cleanup — intentional
    }
};
