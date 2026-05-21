<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetForProduction extends Command
{
    protected $signature   = 'stock365:reset
                                {--yes : Omit confirmation prompt}';
    protected $description = 'Wipe all operational data and re-seed real products. Keeps users, roles, sedes.';

    public function handle(): int
    {
        if (! $this->option('yes')) {
            $this->warn('⚠  This will DELETE all sales, movements, sessions, closings, inventory, and activity logs.');
            if (! $this->confirm('Proceed?', false)) {
                $this->line('Aborted.');
                return 1;
            }
        }

        $this->info('Clearing operational data…');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $tables = [
            'sale_items',
            'sales',
            'cash_closings',
            'cash_sessions',
            'stock_transfer_items',
            'stock_transfers',
            'inventory_receipt_items',
            'inventory_receipts',
            'inventory_movements',
            'stock_alerts',
            'activity_logs',
            'inventories',
            'products',
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
            $this->line("  ✓ truncated <comment>{$table}</comment>");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Re-seed real products (upsert by SKU so SKUs stay stable)
        $this->info('Seeding real products…');
        $this->call('db:seed', ['--class' => 'ProductSeeder', '--no-interaction' => true]);

        $this->newLine();
        $this->info('✅  STOCK365 is ready for production launch.');
        $this->newLine();
        $this->line('Next steps:');
        $this->line('  1. Login as boss → Inventario → <comment>Carga Masiva de Stock</comment>');
        $this->line('  2. Select each sede and load initial inventory');
        $this->line('  3. Operators open cash sessions and start selling');

        return 0;
    }
}
