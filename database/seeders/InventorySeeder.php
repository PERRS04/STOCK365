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
        $sedes = Sede::all();
        $products = Product::all();

        // Crear inventarios iniciales para cada producto en cada sede
        foreach ($sedes as $sede) {
            foreach ($products as $product) {
                Inventory::create([
                    'product_id' => $product->id,
                    'sede_id' => $sede->id,
                    'cantidad_stock' => rand(30, 100),
                    'ultima_actualizacion' => now(),
                ]);
            }
        }
    }
}
