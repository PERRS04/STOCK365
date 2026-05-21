<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\StockAlert;
use Illuminate\Database\Seeder;

class StockAlertSeeder extends Seeder
{
    public function run(): void
    {
        $inventories = Inventory::with('product')->get();

        foreach ($inventories as $inv) {
            if ($inv->cantidad_stock < $inv->product->stock_minimo) {
                StockAlert::create([
                    'product_id'             => $inv->product_id,
                    'sede_id'                => $inv->sede_id,
                    'stock_actual'           => $inv->cantidad_stock,
                    'stock_minimo'           => $inv->product->stock_minimo,
                    'alerta_activa'          => true,
                    'fecha_alerta'           => now()->subHours(rand(1, 72)),
                    'notificacion_enviada'   => false,
                    'notificacion_enviada_at'=> null,
                ]);
            }
        }
    }
}
