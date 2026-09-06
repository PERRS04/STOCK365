<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            SedeSeeder::class,
            UserSeeder::class,
            ProductSeeder::class,
            AlmacenSeeder::class,
            // InventorySeeder, SaleSeeder, etc. are intentionally excluded.
            // Use --seeder=ProductionSeeder for a clean production launch.
            // Run `php artisan stock365:reset` to wipe operational data at any time.
        ]);
    }
}
