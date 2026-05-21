<?php

namespace Database\Seeders;

use App\Models\Sede;
use Illuminate\Database\Seeder;

class SedeSeeder extends Seeder
{
    public function run(): void
    {
        $sedes = [
            [
                'nombre'    => 'Centenario',
                'ciudad'    => 'Guayaquil',
                'ubicacion' => 'Av. del Centenario km 4.5',
                'telefono'  => '04-2345678',
                'email'     => 'centenario@stock365.com',
                'activa'    => true,
            ],
            [
                'nombre'    => 'Bahía',
                'ciudad'    => 'Guayaquil',
                'ubicacion' => 'Malecón Bahía de Caráquez 210',
                'telefono'  => '04-8765432',
                'email'     => 'bahia@stock365.com',
                'activa'    => true,
            ],
            [
                'nombre'    => 'Alborada',
                'ciudad'    => 'Guayaquil',
                'ubicacion' => 'Cdla. Alborada 9va Etapa, Blq. 4',
                'telefono'  => '04-5555555',
                'email'     => 'alborada@stock365.com',
                'activa'    => true,
            ],
            [
                'nombre'    => 'Sauces',
                'ciudad'    => 'Guayaquil',
                'ubicacion' => 'Av. Sauces 9, Km 12.5',
                'telefono'  => '04-4444444',
                'email'     => 'sauces@stock365.com',
                'activa'    => true,
            ],
            [
                'nombre'    => 'Florida',
                'ciudad'    => 'Guayaquil',
                'ubicacion' => 'Cdla. La Florida, Av. Principal',
                'telefono'  => '04-3333333',
                'email'     => 'florida@stock365.com',
                'activa'    => true,
            ],
            [
                'nombre'    => 'Martha',
                'ciudad'    => 'Guayaquil',
                'ubicacion' => 'Ciudadela Martha de Roldós, Calle 12',
                'telefono'  => '04-2222222',
                'email'     => 'martha@stock365.com',
                'activa'    => true,
            ],
        ];

        foreach ($sedes as $sede) {
            Sede::create($sede);
        }
    }
}
