<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeder de producción: solo estructura base, sin datos demo.
 * Uso: php artisan migrate:fresh --seeder=ProductionSeeder
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SedeSeeder::class,
            UserSeeder::class,
            ProductSeeder::class,
        ]);
    }
}
