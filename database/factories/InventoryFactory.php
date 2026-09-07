<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sede;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    public function definition(): array
    {
        return [
            'product_id'           => Product::factory(),
            'sede_id'              => Sede::factory(),
            'almacen_id'           => null,
            'cantidad_stock'       => 0,
            'stock_recomendado'    => 0,
            'ultima_actualizacion' => now(),
        ];
    }
}
