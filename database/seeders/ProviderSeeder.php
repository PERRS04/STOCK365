<?php

namespace Database\Seeders;

use App\Models\Provider;
use Illuminate\Database\Seeder;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            'Cervecería Nacional',
            'Señor Crespo',
            'Punto de Chelo',
        ];

        foreach ($providers as $nombre) {
            Provider::updateOrCreate(['nombre' => $nombre], ['activo' => true]);
        }
    }
}
