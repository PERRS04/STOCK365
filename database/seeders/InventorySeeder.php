<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sede;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $sedes    = Sede::all();
        $products = Product::all();

        // Specific stock levels to create realistic alerts and variance.
        // Key: "{product_sku}_{sede_index}" => quantity (0-indexed sede)
        // Items intentionally below minimum to generate real stock alerts.
        $lowStockOverrides = [
            'PIL-630_1' => 12,  // Bahía - below min 20
            'PIL-355_2' => 18,  // Alborada - below min 30
            'CLV-600_3' => 8,   // Sauces - below min 25
            'CLV-330_0' => 14,  // Centenario - below min 30
            'COR-355_4' => 10,  // Florida - below min 25
            'BUD-473_5' => 7,   // Martha - below min 20
            'HEI-355_1' => 11,  // Bahía - below min 20
            'CUS-355_2' => 9,   // Alborada - below min 25
            'PIL-1L_3'  => 6,   // Sauces - below min 15
            'HEI-00_4'  => 8,   // Florida - below min 15
        ];

        foreach ($sedes as $sedeIndex => $sede) {
            foreach ($products as $product) {
                $key = "{$product->sku}_{$sedeIndex}";

                if (isset($lowStockOverrides[$key])) {
                    $stock = $lowStockOverrides[$key];
                } else {
                    // Normal range: between 1.5x and 4x the minimum
                    $min = $product->stock_minimo;
                    $stock = rand((int)($min * 1.5), $min * 4);
                }

                Inventory::updateOrCreate(
                    ['product_id' => $product->id, 'sede_id' => $sede->id],
                    [
                        'cantidad_stock'       => $stock,
                        'stock_recomendado'    => 24,
                        'ultima_actualizacion' => now()->subHours(rand(1, 48)),
                    ]
                );
            }
        }
    }
}
