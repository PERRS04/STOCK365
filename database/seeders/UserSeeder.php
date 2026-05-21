<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Boss
        $boss = User::updateOrCreate(
            ['email' => 'boss@stock365.com'],
            ['name' => 'Mr Perrs', 'password' => Hash::make('Boss123456'), 'role' => 'boss', 'sede_id' => null, 'active' => true, 'email_verified_at' => now()]
        );
        $boss->syncRoles(['boss']);

        // Supervisor
        $supervisor = User::updateOrCreate(
            ['email' => 'supervisor@stock365.com'],
            ['name' => 'Jr', 'password' => Hash::make('123456'), 'role' => 'supervisor', 'sede_id' => null, 'active' => true, 'email_verified_at' => now()]
        );
        $supervisor->syncRoles(['supervisor']);

        // Operadoras reales por sede — sede_id: 1=Centenario, 2=Bahía, 3=Alborada, 4=Sauces, 5=Florida, 6=Martha
        $operators = [
            ['name' => 'Mariser',  'email' => 'centenario@stock365.com', 'sede_id' => 1],
            ['name' => 'Glendy',   'email' => 'bahia@stock365.com',      'sede_id' => 2],
            ['name' => 'Michelle', 'email' => 'alborada@stock365.com',   'sede_id' => 3],
            ['name' => 'Ana',      'email' => 'sauces@stock365.com',     'sede_id' => 4],
            ['name' => 'Katty',    'email' => 'florida@stock365.com',    'sede_id' => 5],
            ['name' => 'Karolina', 'email' => 'martha@stock365.com',     'sede_id' => 6],
        ];

        foreach ($operators as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'password' => Hash::make('123456'), 'role' => 'operador', 'sede_id' => $data['sede_id'], 'active' => true, 'email_verified_at' => now()]
            );
            $user->syncRoles(['operador']);
        }

        // Eliminar usuarios demo que ya no aplican
        $demoEmails = ['operador@stock365.com', 'maria@stock365.com', 'carlos@stock365.com', 'ana@stock365.com', 'juan@stock365.com', 'rosa@stock365.com', 'diego@stock365.com'];
        User::whereIn('email', $demoEmails)->each(function ($u) {
            $u->roles()->detach();
            $u->forceDelete();
        });
    }
}
