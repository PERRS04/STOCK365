<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\StockAlert;

class AlertService
{
    public function checkStockAlerts(): void
    {
        $inventories = Inventory::with('product', 'sede')
            ->whereRaw('cantidad_stock < (SELECT stock_minimo FROM products WHERE id = inventories.product_id)')
            ->get();

        foreach ($inventories as $inventory) {
            StockAlert::updateOrCreate(
                ['product_id' => $inventory->product_id, 'sede_id' => $inventory->sede_id],
                [
                    'stock_actual' => $inventory->cantidad_stock,
                    'stock_minimo' => $inventory->product->stock_minimo,
                    'alerta_activa' => true,
                    'fecha_alerta' => now(),
                    'notificacion_enviada' => false,
                ]
            );
        }
    }

    public function dismissAlert(int $alertId): void
    {
        StockAlert::find($alertId)?->update(['alerta_activa' => false]);
    }
}
