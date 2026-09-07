<?php

namespace Database\Factories;

use App\Models\Almacen;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlmacenFactory extends Factory
{
    protected $model = Almacen::class;

    public function definition(): array
    {
        return [
            'nombre'      => 'Almacén '.$this->faker->city(),
            'descripcion' => null,
            'activo'      => true,
        ];
    }
}
