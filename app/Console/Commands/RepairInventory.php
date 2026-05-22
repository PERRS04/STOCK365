<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sede;
use App\Models\StockAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RepairInventory extends Command
{
    protected $signature = 'stock365:repair-inventory
                                {--dry-run : Preview changes without writing to database}
                                {--sede=   : Limit repair to a specific sede ID}';

    protected $description = 'Ensure every active product has an inventory record in every active sede, and sync stock alerts.';

    public function handle(): int
    {
        $dryRun     = $this->option('dry-run');
        $sedeFilter = $this->option('sede');

        if ($dryRun) {
            $this->warn('  [DRY RUN] No changes will be written to the database.');
            $this->newLine();
        }

        $now = Carbon::now();

        // ── Load reference data ──────────────────────────────────────────────────
        $products = Product::where('activo', true)->orderBy('nombre')->get();
        $sedes    = Sede::where('activa', true)
            ->when($sedeFilter, fn($q) => $q->where('id', $sedeFilter))
            ->orderBy('nombre')
            ->get();

        if ($products->isEmpty()) {
            $this->error('No hay productos activos en la base de datos.');
            return 1;
        }

        if ($sedes->isEmpty()) {
            $this->error('No hay sedes activas' . ($sedeFilter ? " con ID {$sedeFilter}" : '') . '.');
            return 1;
        }

        $expectedCount = $products->count() * $sedes->count();

        $this->line("  Productos activos : <comment>{$products->count()}</comment>");
        $this->line("  Sedes activas     : <comment>{$sedes->count()}</comment>");
        $this->line("  Combinaciones     : <comment>{$expectedCount}</comment>");
        $this->newLine();

        // ── Step 1: Create missing inventory records ─────────────────────────────
        $this->info('1/4  Auditando registros de inventario...');

        $existingKeys = DB::table('inventories')
            ->select('product_id', 'sede_id')
            ->when($sedeFilter, fn($q) => $q->where('sede_id', $sedeFilter))
            ->get()
            ->map(fn($r) => "{$r->product_id}_{$r->sede_id}")
            ->flip()
            ->all();

        $toInsert          = [];
        $missingBySede     = [];

        foreach ($sedes as $sede) {
            foreach ($products as $product) {
                $key = "{$product->id}_{$sede->id}";
                if (! isset($existingKeys[$key])) {
                    $toInsert[] = [
                        'product_id'           => $product->id,
                        'sede_id'              => $sede->id,
                        'cantidad_stock'       => 0,
                        'stock_recomendado'    => 24,
                        'ultima_actualizacion' => $now,
                        'created_at'           => $now,
                        'updated_at'           => $now,
                    ];
                    $missingBySede[$sede->nombre][] = $product->nombre;
                }
            }
        }

        if (empty($toInsert)) {
            $this->line('     <fg=green>✓ Todos los inventarios ya existen. No hay nada que crear.</>');
        } else {
            $this->line("     <fg=yellow>⚠  " . count($toInsert) . " registros faltantes detectados:</>");
            foreach ($missingBySede as $sedeNombre => $productNames) {
                $this->line("       • <comment>{$sedeNombre}</comment>: " . count($productNames) . ' producto(s)');
                foreach ($productNames as $n) {
                    $this->line("         - {$n}");
                }
            }

            if (! $dryRun) {
                // insertOrIgnore is the safety net against any race condition on the unique key.
                foreach (array_chunk($toInsert, 200) as $chunk) {
                    DB::table('inventories')->insertOrIgnore($chunk);
                }
                $this->line("     <fg=green>✅ " . count($toInsert) . " registros creados (stock=0, recomendado=24).</>");
            }
        }
        $this->newLine();

        // ── Step 2: Sync stock alerts ────────────────────────────────────────────
        $this->info('2/4  Sincronizando alertas de stock...');

        // Load ALL inventories (including the ones we just created) + alerts in bulk
        $allInventories = Inventory::with('product')
            ->when($sedeFilter, fn($q) => $q->where('sede_id', $sedeFilter))
            ->get();

        $allAlerts = StockAlert::when($sedeFilter, fn($q) => $q->where('sede_id', $sedeFilter))
            ->get()
            ->keyBy(fn($a) => "{$a->product_id}_{$a->sede_id}");

        $alertsCreated     = 0;
        $alertsReactivated = 0;
        $alertsDeactivated = 0;

        $alertsToCreate     = [];
        $alertsToReactivate = [];
        $alertIdsToDeactivate = [];

        foreach ($allInventories as $inv) {
            if (! $inv->product) continue;

            $key        = "{$inv->product_id}_{$inv->sede_id}";
            $alert      = $allAlerts->get($key);
            $needsAlert = $inv->cantidad_stock < $inv->product->stock_minimo;

            if ($needsAlert) {
                if (! $alert) {
                    $alertsToCreate[] = [
                        'product_id'              => $inv->product_id,
                        'sede_id'                 => $inv->sede_id,
                        'stock_actual'            => $inv->cantidad_stock,
                        'stock_minimo'            => $inv->product->stock_minimo,
                        'alerta_activa'           => true,
                        'fecha_alerta'            => $now,
                        'notificacion_enviada'    => false,
                        'notificacion_enviada_at' => null,
                        'created_at'              => $now,
                        'updated_at'              => $now,
                    ];
                    $alertsCreated++;
                } elseif (! $alert->alerta_activa) {
                    $alertsToReactivate[] = $alert->id;
                    $alertsReactivated++;
                }
            } else {
                if ($alert && $alert->alerta_activa) {
                    $alertIdsToDeactivate[] = $alert->id;
                    $alertsDeactivated++;
                }
            }
        }

        if (! $dryRun) {
            if (! empty($alertsToCreate)) {
                DB::table('stock_alerts')->insertOrIgnore($alertsToCreate);
            }
            if (! empty($alertsToReactivate)) {
                StockAlert::whereIn('id', $alertsToReactivate)->update(['alerta_activa' => true, 'fecha_alerta' => $now]);
            }
            if (! empty($alertIdsToDeactivate)) {
                StockAlert::whereIn('id', $alertIdsToDeactivate)->update(['alerta_activa' => false]);
            }
        }

        $this->line("     Alertas nuevas      : <comment>{$alertsCreated}</comment>");
        $this->line("     Alertas reactivadas : <comment>{$alertsReactivated}</comment>");
        $this->line("     Alertas desactivadas: <comment>{$alertsDeactivated}</comment>");
        $this->newLine();

        // ── Step 3: Check for orphaned movements ─────────────────────────────────
        $this->info('3/4  Verificando movimientos huérfanos...');

        $orphanedByProduct = DB::table('inventory_movements')
            ->leftJoin('products', 'inventory_movements.product_id', '=', 'products.id')
            ->whereNull('products.id')
            ->count();

        $orphanedBySede = DB::table('inventory_movements')
            ->leftJoin('sedes', 'inventory_movements.sede_id', '=', 'sedes.id')
            ->whereNull('sedes.id')
            ->count();

        if ($orphanedByProduct === 0 && $orphanedBySede === 0) {
            $this->line('     <fg=green>✓ Todos los movimientos tienen referencias válidas.</>');
        } else {
            if ($orphanedByProduct > 0) {
                $this->warn("     ⚠  {$orphanedByProduct} movimiento(s) con producto eliminado.");
            }
            if ($orphanedBySede > 0) {
                $this->warn("     ⚠  {$orphanedBySede} movimiento(s) con sede eliminada.");
            }
            $this->line('     (Estos movimientos son históricos y no se eliminan automáticamente.)');
        }
        $this->newLine();

        // ── Step 4: Summary table ────────────────────────────────────────────────
        $this->info('4/4  Resumen final por sede:');

        $allInventoriesGrouped = Inventory::with('product')
            ->when($sedeFilter, fn($q) => $q->where('sede_id', $sedeFilter))
            ->get()
            ->groupBy('sede_id');

        $activeAlertsBySede = StockAlert::where('alerta_activa', true)
            ->when($sedeFilter, fn($q) => $q->where('sede_id', $sedeFilter))
            ->selectRaw('sede_id, COUNT(*) as total')
            ->groupBy('sede_id')
            ->pluck('total', 'sede_id');

        $rows = [];
        foreach ($sedes as $sede) {
            $sedeInvs    = $allInventoriesGrouped->get($sede->id, collect());
            $total       = $sedeInvs->count();
            $sinStock    = $sedeInvs->where('cantidad_stock', 0)->count();
            $critico     = $sedeInvs->filter(
                fn($i) => $i->product
                    && $i->cantidad_stock > 0
                    && $i->cantidad_stock < $i->product->stock_minimo
            )->count();
            $alertas     = $activeAlertsBySede->get($sede->id, 0);
            $complete    = $total >= $products->count() ? '✓' : "⚠ {$total}/{$products->count()}";

            $rows[] = [
                $sede->nombre,
                $complete,
                $sinStock,
                $critico,
                $alertas,
            ];
        }

        $this->table(
            ['Sede', 'Inventarios', 'Sin stock', 'Bajo mínimo', 'Alertas activas'],
            $rows
        );

        if (! $dryRun) {
            $this->newLine();
            $this->info('✅  Reparación completada. Spatie cache limpiado automáticamente.');
        } else {
            $this->newLine();
            $this->warn('  [DRY RUN] Ejecuta sin --dry-run para aplicar los cambios.');
        }

        return 0;
    }
}
