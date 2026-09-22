<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupplierControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $boss;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        Permission::firstOrCreate([
            'name' => 'products.create',
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => 'boss',
            'guard_name' => 'web',
        ]);

        $this->boss = User::factory()->create();
        $this->boss->givePermissionTo('products.create');
        $this->boss->assignRole('boss');
    }

    #[Test]
    public function creacion_guarda_datos_utiles_en_auditoria(): void
    {
        $response = $this->actingAs($this->boss)->post(
            route('suppliers.store'),
            [
                'nombre' => 'Proveedor Auditoria',
                'ruc_nit' => '0999999999001',
                'email' => 'auditoria@proveedor.test',
                'telefono' => '0999999999',
                'contacto_principal' => 'Carlos Perez',
                'direccion' => 'Guayaquil',
                'observaciones' => 'Proveedor de prueba',
            ]
        );

        $supplier = Provider::where('nombre', 'Proveedor Auditoria')->firstOrFail();

        $response->assertRedirect(route('suppliers.show', $supplier));

        $log = ActivityLog::where('action', 'proveedor.creado')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($supplier->id, $log->model_id);
        $this->assertSame('Proveedor Auditoria', $log->new_values['nombre'] ?? null);
        $this->assertSame('0999999999001', $log->new_values['ruc_nit'] ?? null);
        $this->assertSame('auditoria@proveedor.test', $log->new_values['email'] ?? null);
        $this->assertTrue($log->new_values['activo'] ?? false);
    }

    #[Test]
    public function edicion_guarda_solo_cambios_reales_en_auditoria(): void
    {
        $supplier = Provider::create([
            'nombre' => 'Proveedor Original',
            'ruc_nit' => '0999999999002',
            'email' => 'original@proveedor.test',
            'telefono' => '0900000000',
            'activo' => true,
            'created_by' => $this->boss->id,
        ]);

        $response = $this->actingAs($this->boss)->put(
            route('suppliers.update', $supplier),
            [
                'nombre' => 'Proveedor Modificado',
                'ruc_nit' => '0999999999002',
                'email' => 'nuevo@proveedor.test',
                'telefono' => '0900000000',
            ]
        );

        $response->assertRedirect(route('suppliers.show', $supplier));

        $log = ActivityLog::where('action', 'proveedor.editado')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('Proveedor Original', $log->old_values['nombre'] ?? null);
        $this->assertSame('Proveedor Modificado', $log->new_values['nombre'] ?? null);
        $this->assertSame('original@proveedor.test', $log->old_values['email'] ?? null);
        $this->assertSame('nuevo@proveedor.test', $log->new_values['email'] ?? null);

        $this->assertArrayNotHasKey('ruc_nit', $log->old_values ?? []);
        $this->assertArrayNotHasKey('telefono', $log->old_values ?? []);
    }

    #[Test]
    public function desactivacion_guarda_cambio_de_activo_en_auditoria(): void
    {
        $supplier = Provider::create([
            'nombre' => 'Proveedor Activo',
            'activo' => true,
            'created_by' => $this->boss->id,
        ]);

        $response = $this->actingAs($this->boss)->delete(
            route('suppliers.destroy', $supplier)
        );

        $response->assertRedirect(route('suppliers.index'));

        $log = ActivityLog::where('action', 'proveedor.desactivado')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($supplier->id, $log->model_id);
        $this->assertTrue($log->old_values['activo'] ?? false);
        $this->assertFalse($log->new_values['activo'] ?? true);
    }
}