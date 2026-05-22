<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Models\Sede;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncStock extends Command
{
    protected $signature = 'stock365:sync-stock
                                {--dry-run : Show discrepancies without writing}
                                {--sede=   : Limit to a specific sede ID}';

    protected $description = 'Recompute inventories.cantidad_stock from inventory_movements and fix any divergence.';

    public function handle(): int
    {
        $dryRun     = $this->option('dry-run');
        $sedeFilter = $this->option('sede');

        if ($dryRun) {
            $this->warn('  [DRY RUN] No changes will be written.');
            $this->newLine();
        }

        // ── Null-sede movements diagnostic ───────────────────────────────────────
        $nullMoves = DB::table('inventory_movements')->whereNull('sede_id')->count();
        if ($nullMoves > 0) {
            $this->warn("  ⚠  {$nullMoves} movimiento(s) con sede_id = NULL.");
            $this->line('     Causa probable: recepciones creadas por el jefe (sin sede asignada).');
            $this->line('     Estos movimientos NO se incluyen en el cálculo por sede.');
            $this->newLine();
        }

        // ── Compute expected stock per (product, sede) from movements ─────────────
        $this->info('1/2  Calculando stock correcto desde movimientos...');

        $computed = DB::table('inventory_movements')
            ->whereNotNull('sede_id')
            ->when($sedeFilter, fn($q) => $q->where('sede_id', $sedeFilter))
            ->selectRaw("
                product_id,
                sede_id,
                SUM(CASE
                    WHEN tipo IN ('entrada', 'ajuste') THEN cantidad
                    ELSE -cantidad
                END) as expected_stock
            ")
            ->groupBy('product_id', 'sede_id')
            ->get()
            ->keyBy(fn($r) => "{$r->product_id}_{$r->sede_id}");

        // ── Compare against stored values ─────────────────────────────────────────
        $this->info('2/2  Comparando con inventarios almacenados...');

        $inventories = Inventory::with('product', 'sede')
            ->when($sedeFilter, fn($q) => $q->where('sede_id', $sedeFilter))
            ->get();

        $discrepancies = [];
        $toUpdate      = [];

        foreach ($inventories as $inv) {
            $key  = "{$inv->product_id}_{$inv->sede_id}";
            $row  = $computed->get($key);
            $expected = $row ? max(0, (int) $row->expected_stock) : null;

            if ($expected !== null && $expected !== $inv->cantidad_stock) {
                $discrepancies[] = [
                    $inv->sede?->nombre   ?? '?',
                    substr($inv->product?->nombre ?? '?', 0, 28),
                    $inv->cantidad_stock,
                    $expected,
                    $expected > $inv->cantidad_stock ? '+' . ($expected - $inv->cantidad_stock) : ($expected - $inv->cantidad_stock),
                ];
                $toUpdate[] = ['id' => $inv->id, 'expected' => $expected];
            }
        }

        $this->newLine();

        if (empty($discrepancies)) {
            $this->line('  <fg=green>✓ Todo el stock ya está sincronizado. No hay divergencias.</>');
        } else {
            $this->line('  <fg=yellow>⚠  ' . count($discrepancies) . ' divergencia(s) encontradas:</>');
            $this->newLine();
            $this->table(
                ['Sede', 'Producto', 'Actual (UI)', 'Correcto (mov.)', 'Diferencia'],
                $discrepancies
            );

            if (! $dryRun) {
                DB::transaction(function () use ($toUpdate) {
                    foreach ($toUpdate as $upd) {
                        DB::table('inventories')
                            ->where('id', $upd['id'])
                            ->update([
                                'cantidad_stock'       => $upd['expected'],
                                'ultima_actualizacion' => now(),
                            ]);
                    }
                });
                $this->newLine();
                $this->info('✅  ' . count($toUpdate) . ' registros corregidos. El inventario ahora refleja los movimientos.');
            } else {
                $this->newLine();
                $this->warn('  [DRY RUN] Ejecuta sin --dry-run para aplicar los cambios.');
            }
        }

        // ── Inventory with stock > 0 but no movements (manually seeded) ───────────
        $manualStock = $inventories->filter(function ($inv) use ($computed) {
            $key = "{$inv->product_id}_{$inv->sede_id}";
            return $inv->cantidad_stock > 0 && ! $computed->has($key);
        });

        if ($manualStock->count() > 0) {
            $this->newLine();
            $this->line('  <fg=blue>ℹ  ' . $manualStock->count() . ' registro(s) con stock > 0 sin movimientos (stock inicial/manual — no se toca).</>');
        }

        return 0;
    }
}
