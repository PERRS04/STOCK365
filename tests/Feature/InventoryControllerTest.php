<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InventoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Product $product;
    private Sede $sede;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        Permission::firstOrCreate(['name' => 'inventory.adjust', 'guard_name' => 'web']);

        $this->user = User::factory()->create();
        $this->user->givePermissionTo('inventory.adjust');

        $this->product = Product::factory()->create(['stock_minimo' => 5]);
        $this->sede    = Sede::factory()->create();
    }

    private function postAdjust(array $params): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->user)->post(route('inventory.adjust'), $params);
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

    // ── Test 1: aumento funciona ──────────────────────────────────────────────

    #[Test]
    public function test_ajuste_positivo_incrementa_stock(): void
    {
        $this->makeInventory(10);

        $this->postAdjust([
            'product_id' => $this->product->id,
            'sede_id'    => $this->sede->id,
            'cantidad'   => 5,
            'motivo'     => 'Reposición',
        ]);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 15,
        ]);
    }

    // ── Test 2: disminución funciona ──────────────────────────────────────────

    #[Test]
    public function test_ajuste_negativo_decrementa_stock(): void
    {
        $this->makeInventory(10);

        $this->postAdjust([
            'product_id' => $this->product->id,
            'sede_id'    => $this->sede->id,
            'cantidad'   => -3,
            'motivo'     => 'Ajuste manual',
        ]);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 7,
        ]);
    }

    // ── Tests 4+5: InventoryMovement tipo correcto ────────────────────────────

    #[Test]
    public function test_movimiento_entrada_tiene_tipo_entrada_y_cantidad_positiva(): void
    {
        $this->makeInventory(0);

        $this->postAdjust([
            'product_id' => $this->product->id,
            'sede_id'    => $this->sede->id,
            'cantidad'   => 8,
            'motivo'     => 'Ingreso',
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'sede_id'    => $this->sede->id,
            'tipo'       => 'entrada',
            'cantidad'   => 8,
        ]);
    }

    #[Test]
    public function test_movimiento_salida_tiene_tipo_salida_y_cantidad_positiva(): void
    {
        $this->makeInventory(20);

        $this->postAdjust([
            'product_id' => $this->product->id,
            'sede_id'    => $this->sede->id,
            'cantidad'   => -6,
            'motivo'     => 'Salida ajuste',
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'sede_id'    => $this->sede->id,
            'tipo'       => 'salida',
            'cantidad'   => 6,
        ]);
    }

    // ── Test 6: user_id correcto ──────────────────────────────────────────────

    #[Test]
    public function test_movimiento_registra_user_id_del_usuario_autenticado(): void
    {
        $this->makeInventory(10);

        $this->postAdjust([
            'product_id' => $this->product->id,
            'sede_id'    => $this->sede->id,
            'cantidad'   => 3,
            'motivo'     => 'Verificar user_id',
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'sede_id'    => $this->sede->id,
            'user_id'    => $this->user->id,
        ]);
    }

    // ── Test 7: motivo se conserva ────────────────────────────────────────────

    #[Test]
    public function test_movimiento_conserva_motivo_exacto(): void
    {
        $this->makeInventory(10);

        $this->postAdjust([
            'product_id' => $this->product->id,
            'sede_id'    => $this->sede->id,
            'cantidad'   => 2,
            'motivo'     => 'Motivo de auditoría especial',
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'sede_id'    => $this->sede->id,
            'motivo'     => 'Motivo de auditoría especial',
        ]);
    }

    // ── Test 8: stock insuficiente no deja stock negativo ─────────────────────

    #[Test]
    public function test_stock_insuficiente_no_modifica_stock(): void
    {
        $this->makeInventory(3);

        $this->postAdjust([
            'product_id' => $this->product->id,
            'sede_id'    => $this->sede->id,
            'cantidad'   => -10,
            'motivo'     => 'Intento de negativo',
        ]);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 3,
        ]);
    }

    // ── Test 9: excepción revierte la operación completamente ─────────────────

    #[Test]
    public function test_stock_insuficiente_no_crea_movimiento(): void
    {
        $this->makeInventory(3);
        $countBefore = InventoryMovement::count();

        $this->postAdjust([
            'product_id' => $this->product->id,
            'sede_id'    => $this->sede->id,
            'cantidad'   => -10,
            'motivo'     => 'Debe hacer rollback',
        ]);

        $this->assertEquals($countBefore, InventoryMovement::count());
    }

    // ── Test 10: respuestas HTTP del controller ───────────────────────────────

    #[Test]
    public function test_ajuste_exitoso_redirige_con_mensaje_success(): void
    {
        $this->makeInventory(10);

        $response = $this->postAdjust([
            'product_id' => $this->product->id,
            'sede_id'    => $this->sede->id,
            'cantidad'   => 5,
            'motivo'     => 'Reposición',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Stock ajustado correctamente');
    }

    #[Test]
    public function test_stock_insuficiente_redirige_con_error_en_campo_cantidad(): void
    {
        $this->makeInventory(2);

        $response = $this->postAdjust([
            'product_id' => $this->product->id,
            'sede_id'    => $this->sede->id,
            'cantidad'   => -5,
            'motivo'     => 'Excede stock',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('cantidad');
    }

    // ── Test 11: no afecta inventario de otras sedes ──────────────────────────

    #[Test]
    public function test_ajuste_no_afecta_inventario_de_otras_sedes(): void
    {
        $otraSede = Sede::factory()->create();

        $this->makeInventory(10);
        Inventory::factory()->create([
            'product_id'     => $this->product->id,
            'sede_id'        => $otraSede->id,
            'almacen_id'     => null,
            'cantidad_stock' => 20,
        ]);

        $this->postAdjust([
            'product_id' => $this->product->id,
            'sede_id'    => $this->sede->id,
            'cantidad'   => 5,
            'motivo'     => 'Solo esta sede',
        ]);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 15,
        ]);
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->product->id,
            'sede_id'        => $otraSede->id,
            'cantidad_stock' => 20,
        ]);
    }

    // ── Test 12: controller opera solo sobre sedes, almacen_id siempre null ──

    #[Test]
    public function test_movimiento_de_sede_tiene_almacen_id_null(): void
    {
        $this->makeInventory(0);

        $this->postAdjust([
            'product_id' => $this->product->id,
            'sede_id'    => $this->sede->id,
            'cantidad'   => 5,
            'motivo'     => 'Solo sede',
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->product->id,
            'sede_id'    => $this->sede->id,
            'almacen_id' => null,
        ]);
    }

    // ── Test extra: observaciones se persisten en el movimiento ───────────────

    #[Test]
    public function test_observaciones_se_persisten_en_inventory_movement(): void
    {
        $this->makeInventory(10);

        $this->postAdjust([
            'product_id'    => $this->product->id,
            'sede_id'       => $this->sede->id,
            'cantidad'      => 4,
            'motivo'        => 'Con observaciones',
            'observaciones' => 'Nota de auditoría importante',
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id'    => $this->product->id,
            'sede_id'       => $this->sede->id,
            'observaciones' => 'Nota de auditoría importante',
        ]);
    }
}
