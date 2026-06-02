<?php

namespace App\Console\Commands;

use App\Models\Sede;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DemoResetCommand extends Command
{
    protected $signature   = 'stock365:demo-reset';
    protected $description = 'Wipes and regenerates all DEMO CENTER data';

    public function handle(): int
    {
        $sede = Sede::where('is_demo', true)->first();

        if (! $sede) {
            $this->error('No demo sede found (is_demo = true). Run DemoSeeder first.');
            return self::FAILURE;
        }

        $sedeId = $sede->id;
        $this->info("Demo sede: [{$sedeId}] {$sede->nombre}");

        $this->line('');
        $this->info('Wiping operational data...');

        DB::transaction(function () use ($sedeId) {
            // Sale items depend on sales
            DB::table('sale_items')
                ->whereIn('sale_id', DB::table('sales')->where('sede_id', $sedeId)->pluck('id'))
                ->delete();
            DB::table('sales')->where('sede_id', $sedeId)->delete();

            DB::table('cash_movements')->where('sede_id', $sedeId)->delete();
            DB::table('cash_closings')->where('sede_id', $sedeId)->delete();
            DB::table('cash_sessions')->where('sede_id', $sedeId)->delete();

            DB::table('courtesy_transactions')->where('sede_id', $sedeId)->delete();

            DB::table('stock_alerts')->where('sede_id', $sedeId)->delete();

            // Operational intelligence snapshots
            if (DB::getSchemaBuilder()->hasTable('operational_snapshots')) {
                DB::table('operational_snapshots')->where('sede_id', $sedeId)->delete();
            }

            // Workforce tables
            if (DB::getSchemaBuilder()->hasTable('workforce_performance_scores')) {
                DB::table('workforce_performance_scores')->where('sede_id', $sedeId)->delete();
            }
            if (DB::getSchemaBuilder()->hasTable('workforce_behavioral_patterns')) {
                DB::table('workforce_behavioral_patterns')->where('sede_id', $sedeId)->delete();
            }

            // Activity logs
            DB::table('activity_logs')->where('sede_id', $sedeId)->delete();

            // Inventory (movements + entries)
            if (DB::getSchemaBuilder()->hasTable('inventory_movements')) {
                DB::table('inventory_movements')->where('sede_id', $sedeId)->delete();
            }
            DB::table('inventories')->where('sede_id', $sedeId)->delete();
        });

        $this->line('  ✓ Operational data wiped');

        // Flush intelligence caches
        $this->line('');
        $this->info('Flushing intelligence caches...');
        Cache::forget('exec_snapshot');
        Cache::forget('sede_demo_ids');
        Cache::forget("oi_health_{$sedeId}");
        $this->line('  ✓ Caches flushed');

        // Re-seed
        $this->line('');
        $this->info('Re-seeding demo data...');
        $this->call(DemoSeeder::class);

        $this->line('');
        $this->info('✓ DEMO CENTER reset complete.');
        $this->line("  Login: demo-boss@stock365.com / Demo123456");

        return self::SUCCESS;
    }
}
