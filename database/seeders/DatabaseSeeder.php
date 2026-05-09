<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            SedeSeeder::class,
            ProviderSeeder::class,
            ProductSeeder::class,
            InventorySeeder::class,
        ]);
    }
}
