<?php

namespace Database\Seeders;

use App\Models\Provider;
use Illuminate\Database\Seeder;

class ProviderSeeder extends Seeder
{
    public function run(): void
    {
        $providers = [
            [
                'nombre' => 'Cervecería Pilsener',
                'ruc_nit' => '20123456789',
                'email' => 'ventas@pilsener.com',
                'telefono' => '01-6666666',
                'contacto_principal' => 'Roberto García',
                'direccion' => 'Av. Industrial 1000, Lima',
                'activo' => true,
            ],
            [
                'nombre' => 'Importadora Corona',
                'ruc_nit' => '20987654321',
                'email' => 'info@coronaimpor.com',
                'telefono' => '01-7777777',
                'contacto_principal' => 'Patricia López',
                'direccion' => 'Av. Principales 500, Lima',
                'activo' => true,
            ],
            [
                'nombre' => 'Distribuidora Budweiser',
                'ruc_nit' => '20456789123',
                'email' => 'distribucion@budper.com',
                'telefono' => '01-8888888',
                'contacto_principal' => 'Miguel Rodríguez',
                'direccion' => 'Jr. Comercial 250, Lima',
                'activo' => true,
            ],
            [
                'nombre' => 'Heineken Perú',
                'ruc_nit' => '20789456123',
                'email' => 'ventas@heineken.pe',
                'telefono' => '01-9999999',
                'contacto_principal' => 'Sandra Martínez',
                'direccion' => 'Av. Distribuidor 800, Lima',
                'activo' => true,
            ],
        ];

        foreach ($providers as $provider) {
            Provider::create($provider);
        }
    }
}
