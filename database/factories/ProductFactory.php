<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'sku'           => strtoupper($this->faker->unique()->bothify('??###')),
            'nombre'        => $this->faker->words(3, true),
            'marca'         => $this->faker->company(),
            'tamaño'        => $this->faker->randomElement(['1L', '500ml', '250g', 'UNIT', 'KG']),
            'precio_compra' => $this->faker->randomFloat(2, 5, 100),
            'precio_venta'  => $this->faker->randomFloat(2, 10, 200),
            'stock_minimo'  => 5,
            'descripcion'   => null,
            'imagen'        => null,
            'created_by'    => User::factory(),
            'activo'        => true,
        ];
    }
}
