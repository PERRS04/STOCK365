<?php

namespace Database\Seeders;

use App\Models\Almacen;
use Illuminate\Database\Seeder;

class AlmacenSeeder extends Seeder
{
    public function run(): void
    {
        Almacen::firstOrCreate(
            ['nombre' => 'Almacén Sauces'],
            [
                'descripcion' => 'Almacén independiente de almacenamiento',
                'activo'      => true,
            ]
        );
    }
}
