<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BulkInventoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $boss;
    private Sede $sede;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        Role::firstOrCreate(['name' => 'boss', 'guard_name' => 'web']);

        $this->boss = User::factory()->create();
        $this->boss->assignRole('boss');

        $this->sede = Sede::factory()->create();
    }

    private function makeProduct(): Product
    {
        return Product::factory()->create();
    }

    private function makeInventory(Product $product, Sede $sede, int $stock): Inventory
    {
        return Inventory::factory()->create([
            'product_id'     => $product->id,
            'sede_id'        => $sede->id,
            'almacen_id'     => null,
            'cantidad_stock' => $stock,
        ]);
    }

    private function postSave(array $params): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->boss)->post(route('inventory.bulk-save'), $params);
    }

    // ── 1. Stock sube al target, no suma ──────────────────────────────────────

    #[Test]
    public function test_stock_sube_a_target_no_suma(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, $this->sede, 10);

        $this->postSave([
            'sede_id'    => $this->sede->id,
            'quantities' => [$product->id => 15],
        ]);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 15,
        ]);
    }

    // ── 2. Stock baja al target ───────────────────────────────────────────────

    #[Test]
    public function test_stock_baja_a_target(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, $this->sede, 15);

        $this->postSave([
            'sede_id'    => $this->sede->id,
            'quantities' => [$product->id => 10],
        ]);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 10,
        ]);
    }

    // ── 3. Aumento genera ajuste cantidad positiva ────────────────────────────

    #[Test]
    public function test_aumento_genera_ajuste_cantidad_positiva(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, $this->sede, 10);

        $this->postSave([
            'sede_id'    => $this->sede->id,
            'quantities' => [$product->id => 15],
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'sede_id'    => $this->sede->id,
            'tipo'       => 'ajuste',
            'cantidad'   => 5,
        ]);
    }

    // ── 4. Reducción genera ajuste cantidad negativa ──────────────────────────

    #[Test]
    public function test_reduccion_genera_ajuste_cantidad_negativa(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, $this->sede, 15);

        $this->postSave([
            'sede_id'    => $this->sede->id,
            'quantities' => [$product->id => 10],
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'sede_id'    => $this->sede->id,
            'tipo'       => 'ajuste',
            'cantidad'   => -5,
        ]);
    }

    // ── 5. Sin cambio: no genera movimiento ───────────────────────────────────

    #[Test]
    public function test_sin_cambio_no_genera_movimiento(): void
    {
        $product     = $this->makeProduct();
        $this->makeInventory($product, $this->sede, 10);
        $countBefore = InventoryMovement::count();

        $this->postSave([
            'sede_id'    => $this->sede->id,
            'quantities' => [$product->id => 10],
        ]);

        $this->assertEquals($countBefore, InventoryMovement::count());
    }

    // ── 6. Inventario inexistente → target 10 ────────────────────────────────

    #[Test]
    public function test_inventario_inexistente_crea_registro_y_ajuste_positivo(): void
    {
        $product = $this->makeProduct();

        $this->postSave([
            'sede_id'    => $this->sede->id,
            'quantities' => [$product->id => 10],
        ]);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 10,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'sede_id'    => $this->sede->id,
            'tipo'       => 'ajuste',
            'cantidad'   => 10,
        ]);
    }

    // ── 7. Inventario inexistente → target 0: sin movimiento ─────────────────

    #[Test]
    public function test_inventario_inexistente_target_cero_no_genera_movimiento(): void
    {
        $product     = $this->makeProduct();
        $countBefore = InventoryMovement::count();

        $this->postSave([
            'sede_id'    => $this->sede->id,
            'quantities' => [$product->id => 0],
        ]);

        $this->assertEquals($countBefore, InventoryMovement::count());
    }

    // ── 8. Motivo preservado en movimiento ────────────────────────────────────

    #[Test]
    public function test_motivo_se_preserva_en_movimiento(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, $this->sede, 10);

        $this->postSave([
            'sede_id'    => $this->sede->id,
            'quantities' => [$product->id => 20],
            'motivo'     => 'Inventario inicial especial',
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'motivo'     => 'Inventario inicial especial',
        ]);
    }

    // ── 9. user_id del boss correcto ──────────────────────────────────────────

    #[Test]
    public function test_movimiento_registra_user_id_del_boss(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, $this->sede, 10);

        $this->postSave([
            'sede_id'    => $this->sede->id,
            'quantities' => [$product->id => 20],
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'user_id'    => $this->boss->id,
        ]);
    }

    // ── 10. sede_id correcta en movimiento ────────────────────────────────────

    #[Test]
    public function test_movimiento_tiene_sede_id_correcta(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, $this->sede, 10);

        $this->postSave([
            'sede_id'    => $this->sede->id,
            'quantities' => [$product->id => 20],
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'sede_id'    => $this->sede->id,
        ]);
    }

    // ── 11. almacen_id null ───────────────────────────────────────────────────

    #[Test]
    public function test_movimiento_tiene_almacen_id_null(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, $this->sede, 10);

        $this->postSave([
            'sede_id'    => $this->sede->id,
            'quantities' => [$product->id => 20],
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'almacen_id' => null,
        ]);
    }

    // ── 12. Múltiples productos: todos al target correcto ─────────────────────

    #[Test]
    public function test_multiples_productos_llegan_al_target_correcto(): void
    {
        $p1 = $this->makeProduct();
        $p2 = $this->makeProduct();
        $p3 = $this->makeProduct();
        $this->makeInventory($p1, $this->sede, 5);
        $this->makeInventory($p2, $this->sede, 20);
        $this->makeInventory($p3, $this->sede, 10);

        $this->postSave([
            'sede_id'    => $this->sede->id,
            'quantities' => [
                $p1->id => 15,
                $p2->id => 10,
                $p3->id => 10,
            ],
        ]);

        $this->assertDatabaseHas('inventories', ['product_id' => $p1->id, 'cantidad_stock' => 15]);
        $this->assertDatabaseHas('inventories', ['product_id' => $p2->id, 'cantidad_stock' => 10]);
        $this->assertDatabaseHas('inventories', ['product_id' => $p3->id, 'cantidad_stock' => 10]);
    }

    // ── 13. Orden de envío no afecta resultado (ksort ASC determinista) ───────

    #[Test]
    public function test_orden_de_envio_no_afecta_resultado(): void
    {
        // Products created in ascending ID order by factory.
        // We submit quantities in descending order to verify ksort() normalizes before processing.
        $p1 = $this->makeProduct();
        $p2 = $this->makeProduct();
        $p3 = $this->makeProduct();
        $this->makeInventory($p1, $this->sede, 5);
        $this->makeInventory($p2, $this->sede, 20);
        $this->makeInventory($p3, $this->sede, 10);

        $this->postSave([
            'sede_id'    => $this->sede->id,
            'quantities' => [
                $p3->id => 30,
                $p1->id => 12,
                $p2->id => 8,
            ],
        ]);

        $this->assertDatabaseHas('inventories', ['product_id' => $p1->id, 'cantidad_stock' => 12]);
        $this->assertDatabaseHas('inventories', ['product_id' => $p2->id, 'cantidad_stock' => 8]);
        $this->assertDatabaseHas('inventories', ['product_id' => $p3->id, 'cantidad_stock' => 30]);
    }

    // ── 14. Rollback total si un producto falla ───────────────────────────────

    #[Test]
    public function test_rollback_total_si_un_producto_falla(): void
    {
        // product->id is a low auto-increment value; 99999 sorts after it via ksort.
        // The valid product is processed first (stock changes to 15), then product_id=99999
        // triggers a FK violation → exception rolls back the outer DB::transaction entirely.
        $product = $this->makeProduct();
        $this->makeInventory($product, $this->sede, 10);

        $this->postSave([
            'sede_id'    => $this->sede->id,
            'quantities' => [
                $product->id => 15,
                99999        => 5,
            ],
        ]);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 10,
        ]);
        $this->assertDatabaseMissing('inventory_movements', [
            'product_id' => $product->id,
        ]);
    }

    // ── 15. Cantidad negativa: validation error ───────────────────────────────

    #[Test]
    public function test_cantidad_negativa_retorna_error_de_validacion(): void
    {
        $product     = $this->makeProduct();
        $this->makeInventory($product, $this->sede, 10);
        $countBefore = InventoryMovement::count();

        $response = $this->postSave([
            'sede_id'    => $this->sede->id,
            'quantities' => [$product->id => -1],
        ]);

        $response->assertSessionHasErrors('quantities.' . $product->id);
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'cantidad_stock' => 10,
        ]);
        $this->assertEquals($countBefore, InventoryMovement::count());
    }

    // ── 16. Decimal: validation error ────────────────────────────────────────

    #[Test]
    public function test_decimal_retorna_error_de_validacion(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, $this->sede, 10);

        $response = $this->postSave([
            'sede_id'    => $this->sede->id,
            'quantities' => [$product->id => '5.5'],
        ]);

        $response->assertSessionHasErrors('quantities.' . $product->id);
    }

    // ── 17. Sede inexistente: validation error ────────────────────────────────

    #[Test]
    public function test_sede_inexistente_retorna_error_de_validacion(): void
    {
        $product = $this->makeProduct();

        $response = $this->postSave([
            'sede_id'    => 99999,
            'quantities' => [$product->id => 10],
        ]);

        $response->assertSessionHasErrors('sede_id');
    }

    // ── 18. Quantities ausente: validation error ──────────────────────────────

    #[Test]
    public function test_quantities_ausente_retorna_error_de_validacion(): void
    {
        $response = $this->postSave([
            'sede_id' => $this->sede->id,
        ]);

        $response->assertSessionHasErrors('quantities');
    }

    // ── 19. Valor null: ignorado, stock sin cambios ───────────────────────────

    #[Test]
    public function test_valor_null_es_ignorado(): void
    {
        $p1 = $this->makeProduct();
        $p2 = $this->makeProduct();
        $this->makeInventory($p1, $this->sede, 10);
        $this->makeInventory($p2, $this->sede, 20);
        $countBefore = InventoryMovement::count();

        $this->postSave([
            'sede_id'    => $this->sede->id,
            'quantities' => [
                $p1->id => null,
                $p2->id => null,
            ],
        ]);

        $this->assertEquals($countBefore, InventoryMovement::count());
        $this->assertDatabaseHas('inventories', ['product_id' => $p1->id, 'cantidad_stock' => 10]);
        $this->assertDatabaseHas('inventories', ['product_id' => $p2->id, 'cantidad_stock' => 20]);
    }

    // ── 20. Usuario no Boss: 403 ──────────────────────────────────────────────

    #[Test]
    public function test_usuario_no_boss_recibe_403(): void
    {
        $nonBoss = User::factory()->create();

        $response = $this->actingAs($nonBoss)->post(route('inventory.bulk-save'), [
            'sede_id'    => $this->sede->id,
            'quantities' => [],
        ]);

        $response->assertStatus(403);
    }

    // ── 21. Redirect correcto tras éxito ──────────────────────────────────────

    #[Test]
    public function test_redirige_correctamente_tras_exito(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, $this->sede, 10);

        $response = $this->postSave([
            'sede_id'    => $this->sede->id,
            'quantities' => [$product->id => 15],
        ]);

        $response->assertRedirect(route('inventory.bulk-load', ['sede_id' => $this->sede->id]));
    }

    // ── 22. Flash success preservado ─────────────────────────────────────────

    #[Test]
    public function test_flash_success_preservado(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, $this->sede, 10);

        $response = $this->postSave([
            'sede_id'    => $this->sede->id,
            'quantities' => [$product->id => 15],
        ]);

        $response->assertSessionHas(
            'success',
            "Inventario actualizado: 1 productos modificados en {$this->sede->nombre}."
        );
    }

    // ── 23. ActivityLogger no lanza excepción ─────────────────────────────────

    #[Test]
    public function test_exitoso_completa_sin_excepcion_de_activity_logger(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, $this->sede, 10);

        $response = $this->postSave([
            'sede_id'    => $this->sede->id,
            'quantities' => [$product->id => 20],
        ]);

        $response->assertRedirect();
        $response->assertSessionMissing('error');
    }

    // ── 24. Movimiento generado por service (tipo=ajuste, no entrada/salida) ──

    #[Test]
    public function test_movimiento_es_ajuste_no_entrada_ni_salida(): void
    {
        // Old direct-write code produced tipo='entrada' or 'salida'.
        // Service produces tipo='ajuste'. This proves controller no longer writes directly.
        $product = $this->makeProduct();
        $this->makeInventory($product, $this->sede, 10);

        $this->postSave([
            'sede_id'    => $this->sede->id,
            'quantities' => [$product->id => 20],
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'tipo'       => 'ajuste',
        ]);
        $this->assertDatabaseMissing('inventory_movements', [
            'product_id' => $product->id,
            'tipo'       => 'entrada',
        ]);
        $this->assertDatabaseMissing('inventory_movements', [
            'product_id' => $product->id,
            'tipo'       => 'salida',
        ]);
    }
}
