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
                'nombre' => 'Sede Centro',
                'ciudad' => 'Lima',
                'ubicacion' => 'Jr. Bolognesi 123',
                'telefono' => '01-2345678',
                'email' => 'centro@stock365.com',
                'activa' => true,
            ],
            [
                'nombre' => 'Sede Norte',
                'ciudad' => 'Lima',
                'ubicacion' => 'Av. Tupac Amaru 456',
                'telefono' => '01-8765432',
                'email' => 'norte@stock365.com',
                'activa' => true,
            ],
            [
                'nombre' => 'Sede Sur',
                'ciudad' => 'Lima',
                'ubicacion' => 'Av. Paseo de la República 789',
                'telefono' => '01-5555555',
                'email' => 'sur@stock365.com',
                'activa' => true,
            ],
            [
                'nombre' => 'Sede Este',
                'ciudad' => 'Lima',
                'ubicacion' => 'Av. Javier Prado Este 321',
                'telefono' => '01-4444444',
                'email' => 'este@stock365.com',
                'activa' => true,
            ],
            [
                'nombre' => 'Sede Oeste',
                'ciudad' => 'Lima',
                'ubicacion' => 'Av. Chorrillos 654',
                'telefono' => '01-3333333',
                'email' => 'oeste@stock365.com',
                'activa' => true,
            ],
            [
                'nombre' => 'Sede Centro Comercial',
                'ciudad' => 'Lima',
                'ubicacion' => 'CC Plaza Mayor, Local 45',
                'telefono' => '01-2222222',
                'email' => 'comercial@stock365.com',
                'activa' => true,
            ],
        ];

        foreach ($sedes as $sede) {
            Sede::create($sede);
        }
    }
}
