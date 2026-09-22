<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SaleControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private Sede $sede;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        Permission::firstOrCreate(['name' => 'sales.create', 'guard_name' => 'web']);

        $this->sede    = Sede::factory()->create();
        $this->cashier = User::factory()->create(['sede_id' => $this->sede->id]);
        $this->cashier->givePermissionTo('sales.create');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeProduct(float $priceBuy = 10.00): Product
    {
        return Product::factory()->create(['precio_compra' => $priceBuy]);
    }

    private function makeInventory(Product $product, int $stock): Inventory
    {
        return Inventory::factory()->create([
            'product_id'     => $product->id,
            'sede_id'        => $this->sede->id,
            'almacen_id'     => null,
            'cantidad_stock' => $stock,
        ]);
    }

    private function itemPayload(int $productId, int $cantidad, float $precio = 20.00): array
    {
        return ['product_id' => $productId, 'cantidad' => $cantidad, 'precio_unitario' => $precio];
    }

    private function postSale(array $items, float $descuento = 0): TestResponse
    {
        return $this->actingAs($this->cashier)->postJson(route('sales.store'), [
            'items'     => $items,
            'descuento' => $descuento,
        ]);
    }

    // ── 1. Venta válida — un producto ─────────────────────────────────────────

    #[Test]
    public function test_venta_valida_un_producto_descuenta_stock_exactamente(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 10);

        $response = $this->postSale([$this->itemPayload($product->id, 3)]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 7,
        ]);
    }

    // ── 2. Venta válida — múltiples productos ─────────────────────────────────

    #[Test]
    public function test_venta_valida_multiples_productos_descuenta_cada_stock(): void
    {
        $p1 = $this->makeProduct();
        $p2 = $this->makeProduct();
        $this->makeInventory($p1, 10);
        $this->makeInventory($p2, 8);

        $response = $this->postSale([
            $this->itemPayload($p1->id, 2),
            $this->itemPayload($p2->id, 5),
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('inventories', [
            'product_id' => $p1->id, 'sede_id' => $this->sede->id, 'cantidad_stock' => 8,
        ]);
        $this->assertDatabaseHas('inventories', [
            'product_id' => $p2->id, 'sede_id' => $this->sede->id, 'cantidad_stock' => 3,
        ]);
    }

    // ── 3. Stock se descuenta exactamente una vez por producto ────────────────

    #[Test]
    public function test_stock_se_descuenta_exactamente_una_vez_por_producto(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 10);

        $countBefore = InventoryMovement::count();

        $this->postSale([$this->itemPayload($product->id, 3)]);

        $this->assertEquals($countBefore + 1, InventoryMovement::count());
    }

    // ── 4. No doble descuento ─────────────────────────────────────────────────

    #[Test]
    public function test_no_doble_descuento_en_venta(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 10);

        $this->postSale([$this->itemPayload($product->id, 3)]);

        // Stock should be exactly 7, not 4 or lower (which would indicate double-deduction)
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 7,
        ]);
        $this->assertDatabaseMissing('inventories', [
            'product_id'     => $product->id,
            'cantidad_stock' => 4,
        ]);
    }

    // ── 5. Movimiento tipo = salida ───────────────────────────────────────────

    #[Test]
    public function test_movimiento_tiene_tipo_salida(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 10);

        $this->postSale([$this->itemPayload($product->id, 3)]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'sede_id'    => $this->sede->id,
            'tipo'       => 'salida',
        ]);
    }

    // ── 6. Movimiento cantidad positiva ───────────────────────────────────────

    #[Test]
    public function test_movimiento_tiene_cantidad_positiva(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 10);

        $this->postSale([$this->itemPayload($product->id, 4)]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'tipo'       => 'salida',
            'cantidad'   => 4,
        ]);
    }

    // ── 7. Movimiento reference_type = sale ───────────────────────────────────

    #[Test]
    public function test_movimiento_tiene_reference_type_sale(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 10);

        $this->postSale([$this->itemPayload($product->id, 2)]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id'     => $product->id,
            'reference_type' => 'sale',
        ]);
    }

    // ── 8. Movimiento reference_id = ID real de Sale ──────────────────────────

    #[Test]
    public function test_movimiento_tiene_reference_id_correcto(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 10);

        $response = $this->postSale([$this->itemPayload($product->id, 2)]);

        $saleId = $response->json('sale_id');

        $this->assertDatabaseHas('inventory_movements', [
            'product_id'   => $product->id,
            'reference_id' => $saleId,
        ]);
    }

    // ── 9. Movimiento user_id correcto ────────────────────────────────────────

    #[Test]
    public function test_movimiento_registra_user_id_correcto(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 10);

        $this->postSale([$this->itemPayload($product->id, 2)]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'user_id'    => $this->cashier->id,
        ]);
    }

    // ── 10. Movimiento sede_id correcto ───────────────────────────────────────

    #[Test]
    public function test_movimiento_registra_sede_id_correcta(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 10);

        $this->postSale([$this->itemPayload($product->id, 2)]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'sede_id'    => $this->sede->id,
        ]);
    }

    // ── 11. Movimiento almacen_id = null ──────────────────────────────────────

    #[Test]
    public function test_movimiento_tiene_almacen_id_null(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 10);

        $this->postSale([$this->itemPayload($product->id, 2)]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'almacen_id' => null,
        ]);
    }

    // ── 12. Movimiento motivo = Venta ─────────────────────────────────────────

    #[Test]
    public function test_movimiento_tiene_motivo_venta(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 10);

        $this->postSale([$this->itemPayload($product->id, 2)]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'motivo'     => 'Venta',
        ]);
    }

    // ── 13. Movimiento preserva costo_unitario del producto ───────────────────

    #[Test]
    public function test_movimiento_preserva_costo_unitario_del_producto(): void
    {
        $product = $this->makeProduct(priceBuy: 35.50);
        $this->makeInventory($product, 10);

        $this->postSale([$this->itemPayload($product->id, 2)]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id'     => $product->id,
            'costo_unitario' => 35.50,
        ]);
    }

    // ── 14. Stock insuficiente → 422 ─────────────────────────────────────────

    #[Test]
    public function test_stock_insuficiente_retorna_422(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 2);

        $response = $this->postSale([$this->itemPayload($product->id, 5)]);

        $response->assertStatus(422)->assertJsonStructure(['error']);
    }

    // ── 15. Stock insuficiente → Sale no persiste ─────────────────────────────

    #[Test]
    public function test_stock_insuficiente_no_crea_sale(): void
    {
        $product    = $this->makeProduct();
        $this->makeInventory($product, 2);
        $countBefore = Sale::count();

        $this->postSale([$this->itemPayload($product->id, 5)]);

        $this->assertEquals($countBefore, Sale::count());
    }

    // ── 16. Stock insuficiente → SaleItems no persisten ──────────────────────

    #[Test]
    public function test_stock_insuficiente_no_crea_sale_items(): void
    {
        $product     = $this->makeProduct();
        $this->makeInventory($product, 2);
        $countBefore = SaleItem::count();

        $this->postSale([$this->itemPayload($product->id, 5)]);

        $this->assertEquals($countBefore, SaleItem::count());
    }

    // ── 17. Stock insuficiente → no InventoryMovement ────────────────────────

    #[Test]
    public function test_stock_insuficiente_no_crea_inventory_movement(): void
    {
        $product     = $this->makeProduct();
        $this->makeInventory($product, 2);
        $countBefore = InventoryMovement::count();

        $this->postSale([$this->itemPayload($product->id, 5)]);

        $this->assertEquals($countBefore, InventoryMovement::count());
    }

    // ── 18. Stock insuficiente → inventario no cambia ────────────────────────

    #[Test]
    public function test_stock_insuficiente_no_modifica_inventario(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 2);

        $this->postSale([$this->itemPayload($product->id, 5)]);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 2,
        ]);
    }

    // ── 19. Inventario inexistente → 422 ─────────────────────────────────────

    #[Test]
    public function test_inventario_inexistente_retorna_422(): void
    {
        $product = $this->makeProduct();
        // Deliberately no makeInventory() call

        $response = $this->postSale([$this->itemPayload($product->id, 1)]);

        $response->assertStatus(422)->assertJsonStructure(['error']);
    }

    // ── 20. Inventario inexistente → no se crea automáticamente ──────────────

    #[Test]
    public function test_inventario_inexistente_no_crea_inventario_automaticamente(): void
    {
        $product = $this->makeProduct();
        $countBefore = Inventory::count();

        $this->postSale([$this->itemPayload($product->id, 1)]);

        $this->assertEquals($countBefore, Inventory::count());
    }

    // ── 21. Rollback completo si falla el segundo producto ────────────────────

    #[Test]
    public function test_rollback_completo_si_falla_segundo_producto(): void
    {
        $p1 = $this->makeProduct();
        $p2 = $this->makeProduct();
        $this->makeInventory($p1, 10);
        $this->makeInventory($p2, 1);

        $countSalesBefore     = Sale::count();
        $countMovementsBefore = InventoryMovement::count();

        // p2 stock = 1, requesting 5 → fails
        $response = $this->postSale([
            $this->itemPayload($p1->id, 2),
            $this->itemPayload($p2->id, 5),
        ]);

        $response->assertStatus(422);
        $this->assertEquals($countSalesBefore, Sale::count());
        $this->assertEquals($countMovementsBefore, InventoryMovement::count());
    }

    // ── 22. Primer producto no queda descontado si el segundo falla ───────────

    #[Test]
    public function test_primer_producto_no_queda_descontado_si_segundo_falla(): void
    {
        $p1 = $this->makeProduct();
        $p2 = $this->makeProduct();
        $this->makeInventory($p1, 10);
        $this->makeInventory($p2, 1);

        $this->postSale([
            $this->itemPayload($p1->id, 2),
            $this->itemPayload($p2->id, 5),
        ]);

        // p1 must remain at 10 — rollback must have reverted its deduction
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $p1->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 10,
        ]);
    }

    // ── 23. No queda Sale si falla el stock ───────────────────────────────────

    #[Test]
    public function test_no_queda_sale_si_falla_el_stock(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 1);

        $this->postSale([$this->itemPayload($product->id, 10)]);

        $this->assertDatabaseMissing('sales', ['sede_id' => $this->sede->id, 'estado' => 'completada']);
    }

    // ── 24. No quedan SaleItems si falla el stock ─────────────────────────────

    #[Test]
    public function test_no_quedan_sale_items_si_falla_el_stock(): void
    {
        $product     = $this->makeProduct();
        $this->makeInventory($product, 1);
        $countBefore = SaleItem::count();

        $this->postSale([$this->itemPayload($product->id, 10)]);

        $this->assertEquals($countBefore, SaleItem::count());
    }

    // ── 25. Response exitosa contiene success=true y sale_id correcto ─────────

    #[Test]
    public function test_response_exitosa_contiene_success_y_sale_id(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 10);

        $response = $this->postSale([$this->itemPayload($product->id, 1)]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $saleId = $response->json('sale_id');
        $this->assertNotNull($saleId);
        $this->assertDatabaseHas('sales', ['id' => $saleId, 'sede_id' => $this->sede->id]);
    }

    // ── 26. ActivityLogger no bloquea la venta exitosa ────────────────────────

    #[Test]
    public function test_venta_exitosa_completa_sin_error_de_activity_logger(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 5);

        $response = $this->postSale([$this->itemPayload($product->id, 1)]);

        $response->assertStatus(200);
    }

    // ── 27. Permisos: sales.create obligatorio ────────────────────────────────

    #[Test]
    public function test_usuario_sin_permiso_recibe_403(): void
    {
        $sinPermiso = User::factory()->create(['sede_id' => $this->sede->id]);

        $response = $this->actingAs($sinPermiso)->postJson(route('sales.store'), [
            'items'     => [$this->itemPayload(1, 1)],
            'descuento' => 0,
        ]);

        $response->assertStatus(403);
    }

    // ── 28. Duplicate product_id en el carrito se consolida ───────────────────

    #[Test]
    public function test_items_con_product_id_duplicado_se_consolidan(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 10);

        // Same product sent twice: 2 + 3 = 5 total
        $response = $this->postSale([
            $this->itemPayload($product->id, 2),
            $this->itemPayload($product->id, 3),
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 5,
        ]);

        // Exactly one movement (not two)
        $this->assertEquals(1, InventoryMovement::where('product_id', $product->id)->count());

        // Exactly one SaleItem with combined quantity
        $saleId = $response->json('sale_id');
        $this->assertEquals(1, SaleItem::where('sale_id', $saleId)->count());
        $this->assertDatabaseHas('sale_items', [
            'sale_id'    => $saleId,
            'product_id' => $product->id,
            'cantidad'   => 5,
        ]);
    }

    // ── 29. Items procesados en orden product_id ASC ──────────────────────────

    #[Test]
    public function test_items_enviados_en_orden_inverso_producen_resultado_correcto(): void
    {
        $p1 = $this->makeProduct();
        $p2 = $this->makeProduct();
        $p3 = $this->makeProduct();
        $this->makeInventory($p1, 10);
        $this->makeInventory($p2, 10);
        $this->makeInventory($p3, 10);

        // Sort by id descending to simulate worst-case ordering for deadlocks
        $sorted = collect([$p1, $p2, $p3])->sortByDesc('id')->values();

        $response = $this->postSale($sorted->map(
            fn($p) => $this->itemPayload($p->id, 1)
        )->all());

        $response->assertStatus(200)->assertJson(['success' => true]);

        foreach ([$p1, $p2, $p3] as $product) {
            $this->assertDatabaseHas('inventories', [
                'product_id'     => $product->id,
                'sede_id'        => $this->sede->id,
                'cantidad_stock' => 9,
            ]);
        }
    }

    // ── 30. Venta con stock exactamente igual a la demanda ────────────────────

    #[Test]
    public function test_venta_con_stock_exactamente_igual_a_demanda_es_permitida(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 5);

        $response = $this->postSale([$this->itemPayload($product->id, 5)]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 0,
        ]);
    }

    // ── 31. SaleItem contiene costo_unitario correcto ─────────────────────────

    #[Test]
    public function test_sale_item_preserva_costo_unitario_del_producto(): void
    {
        $product = $this->makeProduct(priceBuy: 25.00);
        $this->makeInventory($product, 10);

        $response = $this->postSale([$this->itemPayload($product->id, 2, 50.00)]);

        $saleId = $response->json('sale_id');

        $this->assertDatabaseHas('sale_items', [
            'sale_id'        => $saleId,
            'product_id'     => $product->id,
            'costo_unitario' => 25.00,
            'precio_unitario' => 50.00,
            'cantidad'       => 2,
        ]);
    }

    // ── 32. Sale contiene sede_id y user_id correctos ─────────────────────────

    #[Test]
    public function test_sale_registra_sede_y_user_correctos(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 10);

        $response = $this->postSale([$this->itemPayload($product->id, 1)]);
        $saleId = $response->json('sale_id');

        $this->assertDatabaseHas('sales', [
            'id'      => $saleId,
            'sede_id' => $this->sede->id,
            'user_id' => $this->cashier->id,
            'estado'  => 'completada',
        ]);
    }

    // ── 33-36. Defensa en profundidad: usuarios sin sede bloqueados del POS ────

    #[Test]
    public function test_operador_almacen_no_puede_acceder_get_pos(): void
    {
        $almacen = Almacen::factory()->create();
        $user = User::factory()->create(['sede_id' => null, 'almacen_id' => $almacen->id]);
        $user->givePermissionTo('sales.create');

        $this->actingAs($user)->get(route('pos.create'))->assertForbidden();
    }

    #[Test]
    public function test_operador_almacen_no_puede_post_sales(): void
    {
        $almacen = Almacen::factory()->create();
        $user = User::factory()->create(['sede_id' => null, 'almacen_id' => $almacen->id]);
        $user->givePermissionTo('sales.create');

        $this->actingAs($user)->postJson(route('sales.store'), [
            'items'     => [$this->itemPayload(1, 1)],
            'descuento' => 0,
        ])->assertForbidden();
    }

    #[Test]
    public function test_usuario_sin_sede_ni_almacen_no_puede_acceder_get_pos(): void
    {
        $user = User::factory()->create(['sede_id' => null, 'almacen_id' => null]);
        $user->givePermissionTo('sales.create');

        $this->actingAs($user)->get(route('pos.create'))->assertForbidden();
    }

    #[Test]
    public function test_operador_con_sede_no_recibe_403_en_pos(): void
    {
        // The cashier has sede_id — our guard must not block them.
        // (Middleware may redirect to open caja, but must NOT return 403.)
        $response = $this->actingAs($this->cashier)->get(route('pos.create'));

        $this->assertNotEquals(403, $response->getStatusCode());
    }
}
