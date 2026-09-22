<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\CourtesyTransaction;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CourtesyControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $approver;
    private Product $product;
    private Sede $sede;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        Permission::firstOrCreate(['name' => 'courtesies.approve', 'guard_name' => 'web']);

        $this->approver = User::factory()->create();
        $this->approver->givePermissionTo('courtesies.approve');

        $this->product = Product::factory()->create(['stock_minimo' => 5]);
        $this->sede    = Sede::factory()->create();
    }

    private function makeInventory(int $stock): Inventory
    {
        return Inventory::factory()->create([
            'product_id'     => $this->product->id,
            'sede_id'        => $this->sede->id,
            'almacen_id'     => null,
            'cantidad_stock' => $stock,
        ]);
    }

    private function makePendingCourtesy(int $quantity = 5): CourtesyTransaction
    {
        return CourtesyTransaction::create([
            'sede_id'         => $this->sede->id,
            'user_id'         => $this->approver->id,
            'product_id'      => $this->product->id,
            'quantity'        => $quantity,
            'tipo'            => 'promocion',
            'motivo'          => 'Test motivo',
            'attachment_path' => 'courtesies/test.jpg',
            'status'          => 'pendiente',
        ]);
    }

    private function postApprove(CourtesyTransaction $courtesy): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->approver)->post(route('courtesies.approve', $courtesy));
    }

    // ── Stock deduction ───────────────────────────────────────────────────────

    #[Test]
    public function test_aprobacion_descuenta_stock_del_inventario(): void
    {
        $this->makeInventory(20);
        $courtesy = $this->makePendingCourtesy(5);

        $this->postApprove($courtesy);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 15,
        ]);
    }

    #[Test]
    public function test_aprobacion_con_stock_exacto_reduce_a_cero(): void
    {
        $this->makeInventory(5);
        $courtesy = $this->makePendingCourtesy(5);

        $this->postApprove($courtesy);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 0,
        ]);
    }

    // ── InventoryMovement correctness ─────────────────────────────────────────

    #[Test]
    public function test_movimiento_tiene_tipo_salida(): void
    {
        $this->makeInventory(20);
        $courtesy = $this->makePendingCourtesy(5);

        $this->postApprove($courtesy);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'sede_id'    => $this->sede->id,
            'tipo'       => 'salida',
        ]);
    }

    #[Test]
    public function test_movimiento_no_usa_tipo_cortesia(): void
    {
        $this->makeInventory(20);
        $courtesy = $this->makePendingCourtesy(5);

        $this->postApprove($courtesy);

        $this->assertDatabaseMissing('inventory_movements', [
            'product_id' => $this->product->id,
            'tipo'       => 'cortesia',
        ]);
    }

    #[Test]
    public function test_movimiento_tiene_reference_type_courtesy(): void
    {
        $this->makeInventory(20);
        $courtesy = $this->makePendingCourtesy(5);

        $this->postApprove($courtesy);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id'     => $this->product->id,
            'reference_type' => 'courtesy',
        ]);
    }

    #[Test]
    public function test_movimiento_tiene_reference_id_de_la_cortesia(): void
    {
        $this->makeInventory(20);
        $courtesy = $this->makePendingCourtesy(5);

        $this->postApprove($courtesy);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id'   => $this->product->id,
            'reference_id' => $courtesy->id,
        ]);
    }

    #[Test]
    public function test_movimiento_tiene_cantidad_positiva(): void
    {
        $this->makeInventory(20);
        $courtesy = $this->makePendingCourtesy(7);

        $this->postApprove($courtesy);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'tipo'       => 'salida',
            'cantidad'   => 7,
        ]);
    }

    #[Test]
    public function test_movimiento_registra_user_id_del_aprobador(): void
    {
        $this->makeInventory(20);
        $courtesy = $this->makePendingCourtesy(5);

        $this->postApprove($courtesy);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'user_id'    => $this->approver->id,
        ]);
    }

    #[Test]
    public function test_movimiento_tiene_almacen_id_null(): void
    {
        $this->makeInventory(20);
        $courtesy = $this->makePendingCourtesy(5);

        $this->postApprove($courtesy);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'almacen_id' => null,
        ]);
    }

    // ── Courtesy status update ────────────────────────────────────────────────

    #[Test]
    public function test_courtesy_status_cambia_a_aprobado(): void
    {
        $this->makeInventory(20);
        $courtesy = $this->makePendingCourtesy(5);

        $this->postApprove($courtesy);

        $this->assertDatabaseHas('courtesy_transactions', [
            'id'     => $courtesy->id,
            'status' => 'aprobado',
        ]);
    }

    #[Test]
    public function test_courtesy_registra_approved_by(): void
    {
        $this->makeInventory(20);
        $courtesy = $this->makePendingCourtesy(5);

        $this->postApprove($courtesy);

        $this->assertDatabaseHas('courtesy_transactions', [
            'id'          => $courtesy->id,
            'approved_by' => $this->approver->id,
        ]);
    }

    // ── Insufficient stock ────────────────────────────────────────────────────

    #[Test]
    public function test_stock_insuficiente_no_modifica_stock(): void
    {
        $this->makeInventory(3);
        $courtesy = $this->makePendingCourtesy(10);

        $this->postApprove($courtesy);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 3,
        ]);
    }

    #[Test]
    public function test_stock_insuficiente_no_crea_movimiento(): void
    {
        $this->makeInventory(3);
        $courtesy    = $this->makePendingCourtesy(10);
        $countBefore = InventoryMovement::count();

        $this->postApprove($courtesy);

        $this->assertEquals($countBefore, InventoryMovement::count());
    }

    #[Test]
    public function test_stock_insuficiente_no_cambia_status_cortesia(): void
    {
        $this->makeInventory(3);
        $courtesy = $this->makePendingCourtesy(10);

        $this->postApprove($courtesy);

        $this->assertDatabaseHas('courtesy_transactions', [
            'id'     => $courtesy->id,
            'status' => 'pendiente',
        ]);
    }

    // ── HTTP responses ────────────────────────────────────────────────────────

    #[Test]
    public function test_aprobacion_exitosa_redirige_a_courtesies_index(): void
    {
        $this->makeInventory(20);
        $courtesy = $this->makePendingCourtesy(5);

        $response = $this->postApprove($courtesy);

        $response->assertRedirect(route('courtesies.index'));
        $response->assertSessionHas('success', 'Cortesía aprobada. Stock descontado correctamente.');
    }

    #[Test]
    public function test_stock_insuficiente_redirige_con_error(): void
    {
        $this->makeInventory(3);
        $courtesy = $this->makePendingCourtesy(10);

        $response = $this->postApprove($courtesy);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function test_cortesia_ya_procesada_redirige_con_error(): void
    {
        $this->makeInventory(20);
        $courtesy = $this->makePendingCourtesy(5);
        $courtesy->update(['status' => 'aprobado']);

        $response = $this->postApprove($courtesy);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Esta cortesía ya fue procesada.');
    }

    #[Test]
    public function test_inventario_inexistente_redirige_con_error(): void
    {
        $courtesy = $this->makePendingCourtesy(5);

        $response = $this->postApprove($courtesy);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function test_inventario_inexistente_no_cambia_status_cortesia(): void
    {
        $courtesy = $this->makePendingCourtesy(5);

        $this->postApprove($courtesy);

        $this->assertDatabaseHas('courtesy_transactions', [
            'id'     => $courtesy->id,
            'status' => 'pendiente',
        ]);
    }

    // ── Audit log ─────────────────────────────────────────────────────────────

    #[Test]
    public function test_aprobacion_guarda_cambio_de_estado_y_sede_en_auditoria(): void
    {
        $this->makeInventory(20);
        $courtesy = $this->makePendingCourtesy(5);

        $this->postApprove($courtesy);

        $log = ActivityLog::where('action', 'cortesia.aprobada')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($courtesy->id, $log->model_id);
        $this->assertSame($this->sede->id, $log->sede_id);
        $this->assertSame('pendiente', $log->old_values['estado'] ?? null);
        $this->assertSame('aprobado', $log->new_values['estado'] ?? null);
    }

    #[Test]
    public function test_rechazo_guarda_estado_motivo_y_sede_en_auditoria(): void
    {
        $courtesy = $this->makePendingCourtesy(5);

        $response = $this->actingAs($this->approver)
            ->patch(route('courtesies.reject', $courtesy), [
                'rejection_reason' => 'Cortesia no autorizada',
            ]);

        $response->assertRedirect(route('courtesies.index'));

        $log = ActivityLog::where('action', 'cortesia.rechazada')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($courtesy->id, $log->model_id);
        $this->assertSame($this->sede->id, $log->sede_id);
        $this->assertSame('pendiente', $log->old_values['estado'] ?? null);
        $this->assertSame('rechazado', $log->new_values['estado'] ?? null);
        $this->assertSame(
            'Cortesia no autorizada',
            $log->new_values['motivo_rechazo'] ?? null
        );
    }

    #[Test]
    public function test_registro_guarda_datos_utiles_en_auditoria(): void
    {
        Permission::firstOrCreate([
            'name' => 'courtesies.create',
            'guard_name' => 'web',
        ]);

        $operator = User::factory()->create([
            'sede_id' => $this->sede->id,
        ]);
        $operator->givePermissionTo('courtesies.create');

        \Illuminate\Support\Facades\Storage::fake('public');

        $response = $this->actingAs($operator)
            ->post(route('courtesies.store'), [
                'product_id' => $this->product->id,
                'quantity' => 3,
                'tipo' => 'promocion',
                'motivo' => 'Cliente frecuente',
                'cliente_nombre' => 'Cliente Test',
                'observaciones' => 'Prueba auditoria',
                'attachment' => \Illuminate\Http\UploadedFile::fake()->image('cortesia.jpg'),
            ]);

        $response->assertRedirect(route('dashboard'));

        $courtesy = CourtesyTransaction::latest('id')->firstOrFail();

        $log = ActivityLog::where('action', 'cortesia.registrada')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($courtesy->id, $log->model_id);
        $this->assertSame($this->sede->id, $log->sede_id);
        $this->assertEmpty($log->old_values ?? []);
        $this->assertSame($this->product->id, $log->new_values['producto_id'] ?? null);
        $this->assertSame(3, $log->new_values['cantidad'] ?? null);
        $this->assertSame('promocion', $log->new_values['tipo'] ?? null);
        $this->assertSame('Cliente frecuente', $log->new_values['motivo'] ?? null);
        $this->assertSame('pendiente', $log->new_values['estado'] ?? null);
    }
}
