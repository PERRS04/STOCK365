<?php

namespace Database\Factories;

use App\Models\Sede;
use Illuminate\Database\Eloquent\Factories\Factory;

class SedeFactory extends Factory
{
    protected $model = Sede::class;

    public function definition(): array
    {
        return [
            'nombre'    => $this->faker->city(),
            'ciudad'    => $this->faker->city(),
            'ubicacion' => $this->faker->address(),
            'telefono'  => $this->faker->phoneNumber(),
            'email'     => $this->faker->safeEmail(),
            'activa'    => true,
            'is_demo'   => false,
        ];
    }
}
