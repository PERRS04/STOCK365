<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'sales.create',
            'sales.edit.same_day',
            'sales.view.own',
            'inventory.view',
            'inventory.adjust',
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',
            'closings.view',
            'closings.approve',
            'reports.view',
            'reports.export',
            'users.manage',
            'receipts.create',
            'receipts.approve',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name]);
        }

        $operador = Role::firstOrCreate(['name' => 'operador']);
        $operador->syncPermissions([
            'sales.create',
            'sales.edit.same_day',
            'sales.view.own',
            'receipts.create',
        ]);

        $supervisor = Role::firstOrCreate(['name' => 'supervisor']);
        $supervisor->syncPermissions([
            'sales.create',
            'sales.edit.same_day',
            'sales.view.own',
            'inventory.view',
            'inventory.adjust',
            'products.view',
            'closings.view',
            'closings.approve',
            'reports.view',
            'receipts.create',
            'receipts.approve',
        ]);

        $boss = Role::firstOrCreate(['name' => 'boss']);
        $boss->syncPermissions(Permission::all());

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
