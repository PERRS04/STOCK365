<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // BOSS user
        User::create([
            'name' => 'Luis (BOSS)',
            'email' => 'boss@stock365.com',
            'password' => Hash::make('password123'),
            'role' => 'boss',
            'sede_id' => null,
            'active' => true,
        ]);

        // Operator users (one per sede)
        User::create([
            'name' => 'María - Sede Centro',
            'email' => 'maria@stock365.com',
            'password' => Hash::make('password123'),
            'role' => 'operator',
            'sede_id' => 1,
            'active' => true,
        ]);

        User::create([
            'name' => 'Carlos - Sede Norte',
            'email' => 'carlos@stock365.com',
            'password' => Hash::make('password123'),
            'role' => 'operator',
            'sede_id' => 2,
            'active' => true,
        ]);

        User::create([
            'name' => 'Ana - Sede Sur',
            'email' => 'ana@stock365.com',
            'password' => Hash::make('password123'),
            'role' => 'operator',
            'sede_id' => 3,
            'active' => true,
        ]);

        User::create([
            'name' => 'Juan - Sede Este',
            'email' => 'juan@stock365.com',
            'password' => Hash::make('password123'),
            'role' => 'operator',
            'sede_id' => 4,
            'active' => true,
        ]);

        User::create([
            'name' => 'Rosa - Sede Oeste',
            'email' => 'rosa@stock365.com',
            'password' => Hash::make('password123'),
            'role' => 'operator',
            'sede_id' => 5,
            'active' => true,
        ]);

        User::create([
            'name' => 'Diego - Sede Centro Comercial',
            'email' => 'diego@stock365.com',
            'password' => Hash::make('password123'),
            'role' => 'operator',
            'sede_id' => 6,
            'active' => true,
        ]);
    }
}
