<?php

namespace App\OperationalIntelligence\Detectors;

use App\Enums\SignalSeverity;
use App\Enums\SignalType;
use App\Models\Inventory;
use App\OperationalIntelligence\Contracts\DetectorContract;
use App\Values\OperationalSignal;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryDetector implements DetectorContract
{
    private const VELOCITY_DAYS  = 7;
    private const DEAD_STOCK_DAYS = 30;
    private const CRITICAL_STOCK  = 0;

    public function detect(): Collection
    {
        $signals = collect();

        $this->detectZeroStock($signals);
        $this->detectVelocityRisk($signals);
        $this->detectDeadStock($signals);

        return $signals;
    }

    private function detectZeroStock(Collection $signals): void
    {
        $items = Inventory::where('cantidad_stock', '<=', self::CRITICAL_STOCK)
            ->with('product:id,nombre,stock_minimo', 'sede:id,nombre')
            ->limit(20)
            ->get();

        foreach ($items as $inv) {
            $updatedAt = $inv->ultima_actualizacion
                ? Carbon::parse($inv->ultima_actualizacion)
                : now();

            $signals->push(new OperationalSignal(
                id:              "inv_zero_{$inv->product_id}_{$inv->sede_id}",
                type:            SignalType::INVENTORY_CRITICAL,
                severity:        SignalSeverity::CRITICAL,
                title:           "Sin stock · {$inv->product?->nombre}",
                body:            "Producto agotado en {$inv->sede?->nombre}. Stock actual: {$inv->cantidad_stock} u.",
                sedeId:          $inv->sede_id,
                sedeName:        $inv->sede?->nombre,
                operatorId:      null,
                operatorName:    null,
                detectedAt:      $updatedAt,
                confidence:      1.0,
                suggestedAction: 'Ordenar reposición o transferencia inmediata',
                context:         ['stock' => $inv->cantidad_stock, 'product_id' => $inv->product_id],
                isPredictive:    false,
            ));
        }
    }

    private function detectVelocityRisk(Collection $signals): void
    {
        $rows = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->join('inventories', function ($join) {
                $join->on('sale_items.product_id', '=', 'inventories.product_id')
                     ->on('sales.sede_id', '=', 'inventories.sede_id');
            })
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sedes', 'inventories.sede_id', '=', 'sedes.id')
            ->where('sales.fecha_venta', '>=', now()->subDays(self::VELOCITY_DAYS))
            ->where('sales.estado', 'completada')
            ->where('inventories.cantidad_stock', '>', 0)
            ->selectRaw('
                sale_items.product_id,
                sales.sede_id,
                products.nombre as product_name,
                sedes.nombre as sede_name,
                inventories.cantidad_stock,
                SUM(sale_items.cantidad) as units_sold
            ')
            ->groupBy(
                'sale_items.product_id', 'sales.sede_id',
                'products.nombre', 'sedes.nombre', 'inventories.cantidad_stock'
            )
            ->limit(100)
            ->get();

        foreach ($rows as $row) {
            $unitsPerDay = $row->units_sold / self::VELOCITY_DAYS;
            if ($unitsPerDay <= 0) continue;

            $daysLeft = $row->cantidad_stock / $unitsPerDay;
            if ($daysLeft > 1) continue;

            $hoursLeft = round($daysLeft * 24);

            $signals->push(new OperationalSignal(
                id:              "inv_velocity_{$row->product_id}_{$row->sede_id}",
                type:            SignalType::INVENTORY_VELOCITY,
                severity:        $daysLeft < 0.5 ? SignalSeverity::CRITICAL : SignalSeverity::WARNING,
                title:           "Agotamiento inminente · {$row->product_name}",
                body:            "A ritmo actual (" . round($unitsPerDay, 1) . " u/día), stock se agota en {$hoursLeft}h en {$row->sede_name}.",
                sedeId:          $row->sede_id,
                sedeName:        $row->sede_name,
                operatorId:      null,
                operatorName:    null,
                detectedAt:      now(),
                confidence:      0.78,
                suggestedAction: 'Programar reposición o transferencia urgente',
                context:         [
                    'current_stock' => $row->cantidad_stock,
                    'units_per_day' => round($unitsPerDay, 2),
                    'hours_remaining' => $hoursLeft,
                    'product_id' => $row->product_id,
                ],
                isPredictive:    true,
            ));
        }
    }

    private function detectDeadStock(Collection $signals): void
    {
        $threshold = now()->subDays(self::DEAD_STOCK_DAYS);

        $items = Inventory::where('cantidad_stock', '>', 0)
            ->where(function ($q) use ($threshold) {
                $q->where('ultima_actualizacion', '<', $threshold)
                  ->orWhereNull('ultima_actualizacion');
            })
            ->with('product:id,nombre', 'sede:id,nombre')
            ->limit(10)
            ->get();

        foreach ($items as $inv) {
            $daysStagnant = $inv->ultima_actualizacion
                ? Carbon::parse($inv->ultima_actualizacion)->diffInDays(now())
                : self::DEAD_STOCK_DAYS;

            $signals->push(new OperationalSignal(
                id:              "inv_dead_{$inv->product_id}_{$inv->sede_id}",
                type:            SignalType::INVENTORY_STAGNANT,
                severity:        SignalSeverity::NOTICE,
                title:           "Stock estancado · {$inv->product?->nombre}",
                body:            "{$inv->cantidad_stock} u sin movimiento por {$daysStagnant} días en {$inv->sede?->nombre}.",
                sedeId:          $inv->sede_id,
                sedeName:        $inv->sede?->nombre,
                operatorId:      null,
                operatorName:    null,
                detectedAt:      now(),
                confidence:      0.70,
                suggestedAction: 'Evaluar descuento, transferencia o retiro del producto',
                context:         ['stock' => $inv->cantidad_stock, 'days_stagnant' => $daysStagnant],
                isPredictive:    false,
            ));
        }
    }
}
