<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\InventoryReceipt;
use App\Models\InventoryReceiptItem;
use App\Models\Product;
use App\Models\Provider;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Sede;
use App\Models\StockAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class InventoryReceiptControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $approver;
    private Sede $sede;
    private InventoryReceipt $receipt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        Permission::firstOrCreate(['name' => 'receipts.approve', 'guard_name' => 'web']);

        $this->approver = User::factory()->create();
        $this->approver->givePermissionTo('receipts.approve');

        $this->sede = Sede::factory()->create();

        $provider = Provider::create(['nombre' => 'Proveedor Test', 'activo' => true]);

        $this->receipt = InventoryReceipt::create([
            'sede_id'       => $this->sede->id,
            'user_id'       => $this->approver->id,
            'provider_id'   => $provider->id,
            'supplier_name' => 'Proveedor Test',
            'monto_pagado'  => 100.00,
            'estado'        => 'pendiente',
        ]);
    }

    private function makeProduct(int $stockMinimo = 5, float $precioCompra = 10.00): Product
    {
        return Product::factory()->create([
            'stock_minimo'  => $stockMinimo,
            'precio_compra' => $precioCompra,
        ]);
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

    private function postApprove(InventoryReceipt $receipt, array $params): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->approver)->post(
            route('inventory-receipts.approve', $receipt),
            $params
        );
    }

    /** Single-item payload. Default: 10 units × $10.00 = $100.00 (matches receipt monto_pagado). */
    private function defaultPayload(Product $product, int $cantidad = 10, float $costo = 10.00): array
    {
        return [
            'items' => [[
                'product_id'     => $product->id,
                'cantidad'       => $cantidad,
                'costo_unitario' => $costo,
            ]],
        ];
    }

    // ── 1. 403 sin permiso ────────────────────────────────────────────────────

    #[Test]
    public function test_403_sin_permiso(): void
    {
        $user    = User::factory()->create();
        $product = $this->makeProduct();

        $response = $this->actingAs($user)->post(
            route('inventory-receipts.approve', $this->receipt),
            $this->defaultPayload($product)
        );

        $response->assertStatus(403);
    }

    // ── 2. Recepción ya aprobada retorna error flash ──────────────────────────

    #[Test]
    public function test_ya_aprobada_retorna_error(): void
    {
        $this->receipt->update(['estado' => 'aprobado']);
        $product = $this->makeProduct();

        $response = $this->postApprove($this->receipt, $this->defaultPayload($product));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ── 3. Recepción ya rechazada retorna error flash ─────────────────────────

    #[Test]
    public function test_ya_rechazada_retorna_error(): void
    {
        $this->receipt->update(['estado' => 'rechazado']);
        $product = $this->makeProduct();

        $response = $this->postApprove($this->receipt, $this->defaultPayload($product));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ── 4. Redirige a index tras éxito ────────────────────────────────────────

    #[Test]
    public function test_redirige_a_index_tras_exito(): void
    {
        $product = $this->makeProduct();

        $response = $this->postApprove($this->receipt, $this->defaultPayload($product));

        $response->assertRedirect(route('inventory-receipts.index'));
    }

    // ── 5. Flash success tras éxito ───────────────────────────────────────────

    #[Test]
    public function test_flash_success_tras_exito(): void
    {
        $product = $this->makeProduct();

        $response = $this->postApprove($this->receipt, $this->defaultPayload($product));

        $response->assertSessionHas('success');
    }

    // ── 6. Stock aumenta correctamente (inventario nuevo) ────────────────────

    #[Test]
    public function test_stock_aumenta_correctamente(): void
    {
        $product = $this->makeProduct();

        $this->postApprove($this->receipt, $this->defaultPayload($product, 10));

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 10,
        ]);
    }

    // ── 7. Stock acumula sobre inventario existente ───────────────────────────

    #[Test]
    public function test_stock_acumula_sobre_inventario_existente(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 5);

        $this->postApprove($this->receipt, $this->defaultPayload($product, 10));

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 15,
        ]);
    }

    // ── 8. Genera movimiento tipo=entrada ────────────────────────────────────

    #[Test]
    public function test_genera_movimiento_tipo_entrada(): void
    {
        $product = $this->makeProduct();

        $this->postApprove($this->receipt, $this->defaultPayload($product));

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'tipo'       => 'entrada',
        ]);
    }

    // ── 9. Movimiento tiene cantidad correcta ────────────────────────────────

    #[Test]
    public function test_movimiento_tiene_cantidad_correcta(): void
    {
        $product = $this->makeProduct();
        $this->receipt->update(['monto_pagado' => 70.00]); // 7 × 10 = 70

        $this->postApprove($this->receipt, $this->defaultPayload($product, 7, 10.00));

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'tipo'       => 'entrada',
            'cantidad'   => 7,
        ]);
    }

    // ── 10. Movimiento tiene sede_id correcto ────────────────────────────────

    #[Test]
    public function test_movimiento_tiene_sede_id_correcto(): void
    {
        $product = $this->makeProduct();

        $this->postApprove($this->receipt, $this->defaultPayload($product));

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'sede_id'    => $this->sede->id,
        ]);
    }

    // ── 11. Movimiento tiene almacen_id null ─────────────────────────────────

    #[Test]
    public function test_movimiento_tiene_almacen_id_null(): void
    {
        $product = $this->makeProduct();

        $this->postApprove($this->receipt, $this->defaultPayload($product));

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'almacen_id' => null,
        ]);
    }

    // ── 12. Movimiento registra user_id del aprobador ────────────────────────

    #[Test]
    public function test_movimiento_registra_user_id_del_aprobador(): void
    {
        $product = $this->makeProduct();

        $this->postApprove($this->receipt, $this->defaultPayload($product));

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'user_id'    => $this->approver->id,
        ]);
    }

    // ── 13. Movimiento tiene costo_unitario correcto ─────────────────────────

    #[Test]
    public function test_movimiento_tiene_costo_unitario_correcto(): void
    {
        $product = $this->makeProduct();
        $this->receipt->update(['monto_pagado' => 85.00]); // 10 × 8.50 = 85

        $this->postApprove($this->receipt, $this->defaultPayload($product, 10, 8.50));

        $this->assertDatabaseHas('inventory_movements', [
            'product_id'     => $product->id,
            'costo_unitario' => 8.50,
        ]);
    }

    // ── 14. Movimiento tiene reference_id correcto ───────────────────────────

    #[Test]
    public function test_movimiento_tiene_reference_id_correcto(): void
    {
        $product = $this->makeProduct();

        $this->postApprove($this->receipt, $this->defaultPayload($product));

        $this->assertDatabaseHas('inventory_movements', [
            'product_id'   => $product->id,
            'reference_id' => $this->receipt->id,
        ]);
    }

    // ── 15. Movimiento tiene reference_type correcto ─────────────────────────

    #[Test]
    public function test_movimiento_tiene_reference_type_correcto(): void
    {
        $product = $this->makeProduct();

        $this->postApprove($this->receipt, $this->defaultPayload($product));

        $this->assertDatabaseHas('inventory_movements', [
            'product_id'     => $product->id,
            'reference_type' => 'receipt',
        ]);
    }

    // ── 16. Precio compra actualizado: costo promedio ponderado ──────────────

    #[Test]
    public function test_precio_compra_actualizado_costo_promedio(): void
    {
        // oldStock=10 @ $10 | receive 10 @ $20 → avg = (100+200)/20 = $15
        $product = $this->makeProduct(5, 10.00);
        $this->makeInventory($product, 10);
        $this->receipt->update(['monto_pagado' => 200.00]);

        $this->postApprove($this->receipt, [
            'items' => [[
                'product_id'     => $product->id,
                'cantidad'       => 10,
                'costo_unitario' => 20.00,
            ]],
        ]);

        $this->assertDatabaseHas('products', [
            'id'            => $product->id,
            'precio_compra' => 15.00,
        ]);
    }

    // ── 17. Primer lote: precio toma el costo del lote ───────────────────────

    #[Test]
    public function test_precio_compra_primer_lote_sin_stock_previo(): void
    {
        // oldStock=0 @ $10 | receive 10 @ $15 → avg = (0+150)/10 = $15
        $product = $this->makeProduct(5, 10.00);
        $this->receipt->update(['monto_pagado' => 150.00]);

        $this->postApprove($this->receipt, [
            'items' => [[
                'product_id'     => $product->id,
                'cantidad'       => 10,
                'costo_unitario' => 15.00,
            ]],
        ]);

        $this->assertDatabaseHas('products', [
            'id'            => $product->id,
            'precio_compra' => 15.00,
        ]);
    }

    // ── 18. Alerta desactivada cuando nuevo stock ≥ stock_minimo ────────────

    #[Test]
    public function test_alerta_desactivada_cuando_stock_suficiente(): void
    {
        $product = $this->makeProduct(5);
        StockAlert::create([
            'product_id'    => $product->id,
            'sede_id'       => $this->sede->id,
            'stock_actual'  => 0,
            'stock_minimo'  => 5,
            'alerta_activa' => true,
            'fecha_alerta'  => now(),
        ]);

        // receive 10 → new stock = 10 ≥ 5 → deactivate
        $this->postApprove($this->receipt, $this->defaultPayload($product, 10));

        $this->assertDatabaseHas('stock_alerts', [
            'product_id'    => $product->id,
            'sede_id'       => $this->sede->id,
            'alerta_activa' => false,
        ]);
    }

    // ── 19. Alerta permanece activa cuando nuevo stock < stock_minimo ────────

    #[Test]
    public function test_alerta_permanece_activa_cuando_stock_insuficiente(): void
    {
        // stock_minimo=20; receive 5 @ $20 = $100 → new stock=5 < 20
        $product = $this->makeProduct(20, 10.00);
        StockAlert::create([
            'product_id'    => $product->id,
            'sede_id'       => $this->sede->id,
            'stock_actual'  => 0,
            'stock_minimo'  => 20,
            'alerta_activa' => true,
            'fecha_alerta'  => now(),
        ]);

        $this->postApprove($this->receipt, $this->defaultPayload($product, 5, 20.00));

        $this->assertDatabaseHas('stock_alerts', [
            'product_id'    => $product->id,
            'sede_id'       => $this->sede->id,
            'alerta_activa' => true,
        ]);
    }

    // ── 20. Estado recepción cambia a aprobado ───────────────────────────────

    #[Test]
    public function test_estado_recepcion_cambia_a_aprobado(): void
    {
        $product = $this->makeProduct();

        $this->postApprove($this->receipt, $this->defaultPayload($product));

        $this->assertDatabaseHas('inventory_receipts', [
            'id'     => $this->receipt->id,
            'estado' => 'aprobado',
        ]);
    }

    // ── 21. aprobado_por registrado ──────────────────────────────────────────

    #[Test]
    public function test_aprobado_por_registrado(): void
    {
        $product = $this->makeProduct();

        $this->postApprove($this->receipt, $this->defaultPayload($product));

        $this->assertDatabaseHas('inventory_receipts', [
            'id'           => $this->receipt->id,
            'aprobado_por' => $this->approver->id,
        ]);
    }

    // ── 22. aprobado_at registrado ───────────────────────────────────────────

    #[Test]
    public function test_aprobado_at_registrado(): void
    {
        $product = $this->makeProduct();

        $this->postApprove($this->receipt, $this->defaultPayload($product));

        $this->assertNotNull(InventoryReceipt::find($this->receipt->id)->aprobado_at);
    }

    // ── 23. notas_aprobacion almacenadas ─────────────────────────────────────

    #[Test]
    public function test_notas_aprobacion_almacenadas(): void
    {
        $product = $this->makeProduct();

        $this->postApprove($this->receipt, array_merge(
            $this->defaultPayload($product),
            ['notas_aprobacion' => 'Verificado y correcto']
        ));

        $this->assertDatabaseHas('inventory_receipts', [
            'id'               => $this->receipt->id,
            'notas_aprobacion' => 'Verificado y correcto',
        ]);
    }

    // ── 24. Se crea InventoryReceiptItem ─────────────────────────────────────

    #[Test]
    public function test_se_crea_inventory_receipt_item(): void
    {
        $product = $this->makeProduct();

        $this->postApprove($this->receipt, $this->defaultPayload($product, 10, 10.00));

        $this->assertDatabaseHas('inventory_receipt_items', [
            'receipt_id'     => $this->receipt->id,
            'product_id'     => $product->id,
            'cantidad'       => 10,
            'costo_unitario' => 10.00,
        ]);
    }

    // ── 25. Múltiples ítems crean múltiples movimientos ──────────────────────

    #[Test]
    public function test_multiples_items_crean_multiples_movimientos(): void
    {
        $p1 = $this->makeProduct();
        $p2 = $this->makeProduct();
        // 5×10 + 5×10 = 100 = monto_pagado ✓
        $this->postApprove($this->receipt, [
            'items' => [
                ['product_id' => $p1->id, 'cantidad' => 5, 'costo_unitario' => 10.00],
                ['product_id' => $p2->id, 'cantidad' => 5, 'costo_unitario' => 10.00],
            ],
        ]);

        $this->assertDatabaseHas('inventory_movements', ['product_id' => $p1->id, 'tipo' => 'entrada']);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $p2->id, 'tipo' => 'entrada']);
    }

    // ── 26. Múltiples ítems: stock correcto por producto ─────────────────────

    #[Test]
    public function test_multiples_items_stock_correcto_por_producto(): void
    {
        $p1 = $this->makeProduct();
        $p2 = $this->makeProduct();
        $this->receipt->update(['monto_pagado' => 130.00]); // 8×10 + 5×10 = 130

        $this->postApprove($this->receipt, [
            'items' => [
                ['product_id' => $p1->id, 'cantidad' => 8, 'costo_unitario' => 10.00],
                ['product_id' => $p2->id, 'cantidad' => 5, 'costo_unitario' => 10.00],
            ],
        ]);

        $this->assertDatabaseHas('inventories', ['product_id' => $p1->id, 'cantidad_stock' => 8]);
        $this->assertDatabaseHas('inventories', ['product_id' => $p2->id, 'cantidad_stock' => 5]);
    }

    // ── 27. CashMovement creado ───────────────────────────────────────────────

    #[Test]
    public function test_cash_movement_creado(): void
    {
        $product = $this->makeProduct();

        $this->postApprove($this->receipt, $this->defaultPayload($product));

        $this->assertDatabaseHas('cash_movements', [
            'sede_id' => $this->sede->id,
            'type'    => 'pago_proveedor',
            'amount'  => 100.00,
        ]);
    }

    // ── 28. CashMovement status=aprobado con sesión activa ───────────────────

    #[Test]
    public function test_cash_movement_aprobado_con_sesion_activa(): void
    {
        CashSession::create([
            'user_id'        => $this->approver->id,
            'sede_id'        => $this->sede->id,
            'opening_amount' => 100,
            'opened_at'      => now(),
            'status'         => 'open',
        ]);

        $product = $this->makeProduct();
        $this->postApprove($this->receipt, $this->defaultPayload($product));

        $this->assertDatabaseHas('cash_movements', [
            'sede_id' => $this->sede->id,
            'type'    => 'pago_proveedor',
            'status'  => 'aprobado',
        ]);
    }

    // ── 29. CashMovement status=pendiente sin sesión activa ──────────────────

    #[Test]
    public function test_cash_movement_pendiente_sin_sesion_activa(): void
    {
        $product = $this->makeProduct();
        $this->postApprove($this->receipt, $this->defaultPayload($product));

        $this->assertDatabaseHas('cash_movements', [
            'sede_id'         => $this->sede->id,
            'type'            => 'pago_proveedor',
            'status'          => 'pendiente',
            'cash_session_id' => null,
        ]);
    }

    // ── 30. Validación: items requerido ───────────────────────────────────────

    #[Test]
    public function test_validacion_items_requerido(): void
    {
        $response = $this->postApprove($this->receipt, []);

        $response->assertSessionHasErrors('items');
    }

    // ── 31. Validación: cantidad mínimo 1 ─────────────────────────────────────

    #[Test]
    public function test_validacion_cantidad_minimo_1(): void
    {
        $product = $this->makeProduct();

        $response = $this->postApprove($this->receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 0, 'costo_unitario' => 10]],
        ]);

        $response->assertSessionHasErrors('items.0.cantidad');
    }

    // ── 32. Validación: cantidad debe ser entero ───────────────────────────────

    #[Test]
    public function test_validacion_cantidad_debe_ser_entero(): void
    {
        $product = $this->makeProduct();

        $response = $this->postApprove($this->receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => '5.5', 'costo_unitario' => 10]],
        ]);

        $response->assertSessionHasErrors('items.0.cantidad');
    }

    // ── 33. Validación: product_id debe existir ───────────────────────────────

    #[Test]
    public function test_validacion_product_id_debe_existir(): void
    {
        $response = $this->postApprove($this->receipt, [
            'items' => [['product_id' => 99999, 'cantidad' => 5, 'costo_unitario' => 10]],
        ]);

        $response->assertSessionHasErrors('items.0.product_id');
    }

    // ── 34. Validación: costo_unitario requerido ──────────────────────────────

    #[Test]
    public function test_validacion_costo_unitario_requerido(): void
    {
        $product = $this->makeProduct();

        $response = $this->postApprove($this->receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 10]],
        ]);

        $response->assertSessionHasErrors('items.0.costo_unitario');
    }

    // ── 35. Monto mismatch retorna error; no modifica inventario ─────────────

    #[Test]
    public function test_monto_mismatch_retorna_error(): void
    {
        $product     = $this->makeProduct();
        $invBefore   = Inventory::count();
        $movBefore   = InventoryMovement::count();

        // receipt monto_pagado = 100; items total = 5 × 10 = 50 → mismatch
        $response = $this->postApprove($this->receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 5, 'costo_unitario' => 10.00]],
        ]);

        $response->assertSessionHasErrors('monto_items');
        $this->assertEquals($invBefore, Inventory::count());
        $this->assertEquals($movBefore, InventoryMovement::count());
    }

    // ── 36. Movimiento tipo=entrada, no tipo=ajuste (regresión) ──────────────

    #[Test]
    public function test_movimiento_tipo_entrada_no_ajuste(): void
    {
        $product = $this->makeProduct();

        $this->postApprove($this->receipt, $this->defaultPayload($product));

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'tipo'       => 'entrada',
        ]);
        $this->assertDatabaseMissing('inventory_movements', [
            'product_id' => $product->id,
            'tipo'       => 'ajuste',
        ]);
    }

    // ── 37. Items enviados en orden inverso → resultado correcto (sortBy ASC) ─

    #[Test]
    public function test_items_ordenados_asc_no_afecta_resultado(): void
    {
        $p1 = $this->makeProduct();
        $p2 = $this->makeProduct(); // p2.id > p1.id (factory is sequential)
        $this->receipt->update(['monto_pagado' => 130.00]); // 8×10 + 5×10 = 130

        // Submit reversed: p2 first, p1 second
        $this->postApprove($this->receipt, [
            'items' => [
                ['product_id' => $p2->id, 'cantidad' => 5,  'costo_unitario' => 10.00],
                ['product_id' => $p1->id, 'cantidad' => 8,  'costo_unitario' => 10.00],
            ],
        ]);

        $this->assertDatabaseHas('inventories', ['product_id' => $p1->id, 'cantidad_stock' => 8]);
        $this->assertDatabaseHas('inventories', ['product_id' => $p2->id, 'cantidad_stock' => 5]);
    }

    // ── 38. sede_id_override ignorado cuando receipt ya tiene sede ────────────

    #[Test]
    public function test_override_sede_ignorado_si_receipt_tiene_sede(): void
    {
        $otraSede = Sede::factory()->create();
        $product  = $this->makeProduct();

        $this->postApprove($this->receipt, array_merge(
            $this->defaultPayload($product),
            ['sede_id_override' => $otraSede->id]
        ));

        $this->assertDatabaseHas('inventory_receipts', [
            'id'      => $this->receipt->id,
            'sede_id' => $this->sede->id,
        ]);
        $this->assertDatabaseHas('inventories', [
            'product_id' => $product->id,
            'sede_id'    => $this->sede->id,
        ]);
        $this->assertDatabaseMissing('inventories', [
            'product_id' => $product->id,
            'sede_id'    => $otraSede->id,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // CIERRE DE COBERTURA CRÍTICA
    // ═══════════════════════════════════════════════════════════════════════════

    // ── 39. Doble aprobación: idempotencia secuencial ─────────────────────────

    #[Test]
    public function test_doble_aprobacion_es_idempotente(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 10);
        // 5 × $20 = $100 = monto_pagado ✓
        $payload = [
            'items' => [['product_id' => $product->id, 'cantidad' => 5, 'costo_unitario' => 20.00]],
        ];

        // ── Primera aprobación ───────────────────────────────────────────────
        $this->postApprove($this->receipt, $payload);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'cantidad_stock' => 15, // 10 + 5
        ]);
        $this->assertDatabaseHas('inventory_receipts', [
            'id'     => $this->receipt->id,
            'estado' => 'aprobado',
        ]);

        $itemsAfterFirst = InventoryReceiptItem::where('receipt_id', $this->receipt->id)->count();
        $movsAfterFirst  = InventoryMovement::where('reference_id', $this->receipt->id)
                                            ->where('reference_type', 'receipt')->count();
        $cashAfterFirst  = CashMovement::where('sede_id', $this->sede->id)
                                       ->where('type', 'pago_proveedor')->count();

        // ── Segunda aprobación: debe rechazarse ──────────────────────────────
        $response = $this->postApprove($this->receipt, $payload);
        $response->assertRedirect();
        $response->assertSessionHas('error');

        // Stock sigue en 15, no 20
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'cantidad_stock' => 15,
        ]);

        // Conteos no aumentaron
        $this->assertEquals(
            $itemsAfterFirst,
            InventoryReceiptItem::where('receipt_id', $this->receipt->id)->count()
        );
        $this->assertEquals(
            $movsAfterFirst,
            InventoryMovement::where('reference_id', $this->receipt->id)
                             ->where('reference_type', 'receipt')->count()
        );
        $this->assertEquals(
            $cashAfterFirst,
            CashMovement::where('sede_id', $this->sede->id)
                        ->where('type', 'pago_proveedor')->count()
        );

        // Receipt sigue aprobado (no regresó a pendiente)
        $this->assertDatabaseHas('inventory_receipts', [
            'id'     => $this->receipt->id,
            'estado' => 'aprobado',
        ]);
    }

    // ── 40. Rollback multi-item: excepción real, sin cambios parciales ────────

    #[Test]
    public function test_rollback_multi_item_sin_cambios_parciales(): void
    {
        // product A: valid and active, stock = 10
        // product B: soft-deleted → Product::find(B) returns null inside the transaction
        //            → TypeError on $product->update() → real MySQL rollback
        $productA = $this->makeProduct();
        $productB = $this->makeProduct();
        $this->makeInventory($productA, 10);

        // Soft-delete B. exists:products,id validation passes (row still in table).
        // Product::find() inside the controller uses the default scope → returns null.
        $productB->delete();

        // 5×10 + 5×10 = 100 = monto_pagado ✓ — A.id < B.id so A is sorted first
        $this->postApprove($this->receipt, [
            'items' => [
                ['product_id' => $productA->id, 'cantidad' => 5, 'costo_unitario' => 10.00],
                ['product_id' => $productB->id, 'cantidad' => 5, 'costo_unitario' => 10.00],
            ],
        ]);

        // Stock A must be rolled back (10 + 5 reverted to 10)
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $productA->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 10,
        ]);

        // 0 InventoryReceiptItem persisted for this receipt
        $this->assertEquals(
            0,
            InventoryReceiptItem::where('receipt_id', $this->receipt->id)->count()
        );

        // 0 InventoryMovement persisted for this receipt
        $this->assertEquals(
            0,
            InventoryMovement::where('reference_id', $this->receipt->id)
                             ->where('reference_type', 'receipt')->count()
        );

        // Receipt remains pendiente with null approval fields
        $this->assertDatabaseHas('inventory_receipts', [
            'id'           => $this->receipt->id,
            'estado'       => 'pendiente',
            'aprobado_por' => null,
            'aprobado_at'  => null,
        ]);

        // 0 CashMovement from this approval
        $this->assertEquals(
            0,
            CashMovement::where('sede_id', $this->sede->id)
                        ->where('type', 'pago_proveedor')->count()
        );
    }

    // ── 41. sede_id_override exitoso cuando receipt.sede_id es null ──────────
    //
    // inventory_receipts.sede_id is NOT NULL in the schema. We temporarily
    // ALTER the column to nullable so we can set it to NULL and exercise the
    // override code path. The finally block restores NOT NULL unconditionally.

    #[Test]
    public function test_sede_override_exito_con_sede_null(): void
    {
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE inventory_receipts MODIFY COLUMN sede_id BIGINT UNSIGNED NULL'
        );

        try {
            $this->receipt->update(['sede_id' => null]);

            $otraSede = Sede::factory()->create();
            $product  = $this->makeProduct();

            $response = $this->postApprove($this->receipt, array_merge(
                $this->defaultPayload($product),           // 10 × $10 = $100 ✓
                ['sede_id_override' => $otraSede->id]
            ));

            $response->assertRedirect(route('inventory-receipts.index'));
            $response->assertSessionHas('success');

            // Receipt got the override sede
            $this->assertDatabaseHas('inventory_receipts', [
                'id'      => $this->receipt->id,
                'sede_id' => $otraSede->id,
                'estado'  => 'aprobado',
            ]);

            // Stock entered into override sede
            $this->assertDatabaseHas('inventories', [
                'product_id'     => $product->id,
                'sede_id'        => $otraSede->id,
                'cantidad_stock' => 10,
            ]);

            // Movement records override sede, null almacen
            $this->assertDatabaseHas('inventory_movements', [
                'product_id' => $product->id,
                'sede_id'    => $otraSede->id,
                'almacen_id' => null,
                'tipo'       => 'entrada',
            ]);
        } finally {
            \Illuminate\Support\Facades\DB::table('inventory_receipts')
                ->whereNull('sede_id')
                ->update(['sede_id' => $this->sede->id]);
            \Illuminate\Support\Facades\DB::statement(
                'ALTER TABLE inventory_receipts MODIFY COLUMN sede_id BIGINT UNSIGNED NOT NULL'
            );
        }
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // ETAPA 6D — PO-RECEIPT APPROVAL
    // ═══════════════════════════════════════════════════════════════════════════

    // ── PO-receipt helpers ────────────────────────────────────────────────────

    private function makePoProvider(): Provider
    {
        return Provider::create(['nombre' => 'Proveedor PO ' . uniqid(), 'activo' => true]);
    }

    private function makePo(Provider $provider, string $estado = 'enviado'): PurchaseOrder
    {
        return PurchaseOrder::create([
            'provider_id'  => $provider->id,
            'created_by'   => $this->approver->id,
            'fecha_pedido' => now()->toDateString(),
            'total'        => 100.00,
            'estado'       => $estado,
        ]);
    }

    private function addPoItem(PurchaseOrder $po, Product $product, int $cantidad, int $cantidadRecibida = 0): PurchaseOrderItem
    {
        return $po->items()->create([
            'product_id'        => $product->id,
            'cantidad'          => $cantidad,
            'precio_unitario'   => 10.00,
            'subtotal'          => $cantidad * 10.00,
            'cantidad_recibida' => $cantidadRecibida,
        ]);
    }

    /**
     * Creates a pending PO-receipt (purchase_order_id set, monto_pagado=0, sede_id=$sede->id).
     */
    private function makePoReceipt(PurchaseOrder $po, Sede $sede, array $overrides = []): InventoryReceipt
    {
        return InventoryReceipt::create(array_merge([
            'purchase_order_id' => $po->id,
            'sede_id'           => $sede->id,
            'user_id'           => $this->approver->id,
            'provider_id'       => $po->provider_id,
            'supplier_name'     => 'Proveedor PO Test',
            'monto_pagado'      => 0,
            'estado'            => 'pendiente',
        ], $overrides));
    }

    // ── 43. PO-receipt: redirige a index tras aprobación ─────────────────────

    #[Test]
    public function test_po_receipt_redirige_a_index_tras_aprobacion(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $response = $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 8.00]],
        ]);

        $response->assertRedirect(route('inventory-receipts.index'));
    }

    // ── 44. PO-receipt: flash success tras aprobación ────────────────────────

    #[Test]
    public function test_po_receipt_flash_success_tras_aprobacion(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $response = $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 5.00]],
        ]);

        $response->assertSessionHas('success');
    }

    // ── 45. PO-receipt: estado cambia a aprobado ──────────────────────────────

    #[Test]
    public function test_po_receipt_estado_cambia_a_aprobado(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 10.00]],
        ]);

        $this->assertDatabaseHas('inventory_receipts', [
            'id'     => $receipt->id,
            'estado' => 'aprobado',
        ]);
    }

    // ── 46. PO-receipt: aprobado_por registrado ───────────────────────────────

    #[Test]
    public function test_po_receipt_aprobado_por_registrado(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 10.00]],
        ]);

        $this->assertDatabaseHas('inventory_receipts', [
            'id'           => $receipt->id,
            'aprobado_por' => $this->approver->id,
        ]);
    }

    // ── 47. PO-receipt: guard bypassed — items total ≠ monto_pagado=0 ─────────

    #[Test]
    public function test_po_receipt_guard_bypassed_items_total_no_coincide_con_monto_pagado(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede); // monto_pagado = 0

        // items total = 10 × $15 = $150, monto_pagado = 0 → would fail for direct receipt
        $response = $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 15.00]],
        ]);

        $response->assertSessionMissing('errors');
        $response->assertRedirect(route('inventory-receipts.index'));
    }

    // ── 48. PO-receipt: costo libre — distintos costos aceptados ─────────────

    #[Test]
    public function test_po_receipt_costo_libre_acepta_cualquier_costo_unitario(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 5, 5);
        $receipt = $this->makePoReceipt($po, $this->sede);

        // costo_unitario = 0 is allowed (min:0 in validation)
        $response = $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 5, 'costo_unitario' => 0]],
        ]);

        $response->assertRedirect(route('inventory-receipts.index'));
    }

    // ── 49. PO-receipt: NO crea CashMovement ─────────────────────────────────

    #[Test]
    public function test_po_receipt_no_crea_cash_movement(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $before = CashMovement::count();

        $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 10.00]],
        ]);

        $this->assertEquals($before, CashMovement::count());
    }

    // ── 50. PO-receipt: NO crea CashMovement aunque haya sesión activa ────────

    #[Test]
    public function test_po_receipt_no_crea_cash_movement_con_sesion_activa(): void
    {
        CashSession::create([
            'user_id'        => $this->approver->id,
            'sede_id'        => $this->sede->id,
            'opening_amount' => 500,
            'opened_at'      => now(),
            'status'         => 'open',
        ]);

        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $before = CashMovement::count();

        $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 10.00]],
        ]);

        $this->assertEquals($before, CashMovement::count());
    }

    // ── 51. Direct receipt: sigue creando CashMovement (regresión) ───────────

    #[Test]
    public function test_direct_receipt_sigue_creando_cash_movement_regresion(): void
    {
        $product = $this->makeProduct();

        $before = CashMovement::count();

        // $this->receipt is direct (purchase_order_id=null), monto_pagado=100
        $this->postApprove($this->receipt, $this->defaultPayload($product));

        $this->assertEquals($before + 1, CashMovement::count());
    }

    // ── 52. Direct receipt: monto mismatch sigue bloqueando (regresión) ───────

    #[Test]
    public function test_direct_receipt_monto_mismatch_sigue_bloqueando_regresion(): void
    {
        $product = $this->makeProduct();

        // receipt monto_pagado=100, items total = 5×10 = 50 → mismatch for direct receipt
        $response = $this->postApprove($this->receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 5, 'costo_unitario' => 10.00]],
        ]);

        $response->assertSessionHasErrors('monto_items');
    }

    // ── 53. PO-receipt: stock aumenta correctamente ───────────────────────────

    #[Test]
    public function test_po_receipt_stock_aumenta_correctamente(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 8, 8);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 8, 'costo_unitario' => 12.00]],
        ]);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 8,
        ]);
    }

    // ── 54. PO-receipt: stock acumula sobre existente ─────────────────────────

    #[Test]
    public function test_po_receipt_stock_acumula_sobre_existente(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->makeInventory($product, 20);
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 5.00]],
        ]);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 30,
        ]);
    }

    // ── 55. PO-receipt: genera movimiento tipo=entrada ────────────────────────

    #[Test]
    public function test_po_receipt_genera_movimiento_tipo_entrada(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 6, 6);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 6, 'costo_unitario' => 10.00]],
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'tipo'       => 'entrada',
            'cantidad'   => 6,
        ]);
    }

    // ── 56. PO-receipt: movimiento tiene reference_type=receipt ──────────────

    #[Test]
    public function test_po_receipt_movimiento_reference_type_receipt(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 5, 5);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 5, 'costo_unitario' => 10.00]],
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id'     => $product->id,
            'reference_id'   => $receipt->id,
            'reference_type' => 'receipt',
        ]);
    }

    // ── 57. PO cambia a recibido tras aprobación ──────────────────────────────

    #[Test]
    public function test_po_cambia_a_recibido_tras_aprobacion(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 10.00]],
        ]);

        $this->assertDatabaseHas('purchase_orders', [
            'id'     => $po->id,
            'estado' => 'recibido',
        ]);
    }

    // ── 58. PO en estado pendiente bloquea aprobación ─────────────────────────

    #[Test]
    public function test_po_pendiente_bloquea_aprobacion(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov, 'pendiente'); // not 'enviado'
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $response = $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 10.00]],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('inventory_receipts', ['id' => $receipt->id, 'estado' => 'pendiente']);
    }

    // ── 59. PO ya recibido bloquea aprobación ────────────────────────────────

    #[Test]
    public function test_po_recibido_bloquea_aprobacion(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov, 'recibido'); // already received
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $response = $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 10.00]],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'estado' => 'recibido']);
    }

    // ── 60. PO sin cantidades registradas bloquea aprobación ─────────────────

    #[Test]
    public function test_po_sin_cantidades_registradas_bloquea_aprobacion(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 0); // cantidad_recibida = 0
        $receipt = $this->makePoReceipt($po, $this->sede);

        $response = $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 10.00]],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ── 61. Producto extra en items bloquea aprobación ────────────────────────

    #[Test]
    public function test_producto_extra_en_items_bloquea_aprobacion(): void
    {
        $prov     = $this->makePoProvider();
        $po       = $this->makePo($prov);
        $product  = $this->makeProduct();
        $stranger = $this->makeProduct(); // not in PO
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $response = $this->postApprove($receipt, [
            'items' => [
                ['product_id' => $product->id,  'cantidad' => 10, 'costo_unitario' => 10.00],
                ['product_id' => $stranger->id, 'cantidad' => 5,  'costo_unitario' => 10.00],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('inventory_receipts', ['id' => $receipt->id, 'estado' => 'pendiente']);
    }

    // ── 62. Producto faltante en items bloquea aprobación ─────────────────────

    #[Test]
    public function test_producto_faltante_en_items_bloquea_aprobacion(): void
    {
        $prov  = $this->makePoProvider();
        $po    = $this->makePo($prov);
        $prodA = $this->makeProduct();
        $prodB = $this->makeProduct();
        $this->addPoItem($po, $prodA, 10, 10);
        $this->addPoItem($po, $prodB, 5, 5);
        $receipt = $this->makePoReceipt($po, $this->sede);

        // Only submitting prodA, missing prodB
        $response = $this->postApprove($receipt, [
            'items' => [
                ['product_id' => $prodA->id, 'cantidad' => 10, 'costo_unitario' => 10.00],
            ],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ── 63. Cantidad incorrecta bloquea aprobación ────────────────────────────

    #[Test]
    public function test_cantidad_incorrecta_bloquea_aprobacion(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 7); // cantidad_recibida = 7
        $receipt = $this->makePoReceipt($po, $this->sede);

        // Submit 10 instead of 7
        $response = $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 10.00]],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('inventory_receipts', ['id' => $receipt->id, 'estado' => 'pendiente']);
    }

    // ── 64. Cantidad correcta = aprobación exitosa ───────────────────────────

    #[Test]
    public function test_cantidad_correcta_aprobacion_exitosa(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 7);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $response = $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 7, 'costo_unitario' => 10.00]],
        ]);

        $response->assertRedirect(route('inventory-receipts.index'));
        $this->assertDatabaseHas('inventory_receipts', ['id' => $receipt->id, 'estado' => 'aprobado']);
    }

    // ── 65. Dos PO items mismo producto: cantidades se agrupan ───────────────

    #[Test]
    public function test_dos_po_items_mismo_producto_cantidades_agrupadas(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        // Two PO rows for the same product: 3 + 4 = 7 total
        $this->addPoItem($po, $product, 5, 3);
        $this->addPoItem($po, $product, 8, 4);
        $receipt = $this->makePoReceipt($po, $this->sede);

        // Submit grouped total: 7
        $response = $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 7, 'costo_unitario' => 10.00]],
        ]);

        $response->assertRedirect(route('inventory-receipts.index'));
        $this->assertDatabaseHas('inventory_receipts', ['id' => $receipt->id, 'estado' => 'aprobado']);
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 7,
        ]);
    }

    // ── 66. Multi-item PO: ambos productos aprobados correctamente ───────────

    #[Test]
    public function test_po_multi_item_stock_correcto(): void
    {
        $prov  = $this->makePoProvider();
        $po    = $this->makePo($prov);
        $prodA = $this->makeProduct();
        $prodB = $this->makeProduct();
        $this->addPoItem($po, $prodA, 10, 6);
        $this->addPoItem($po, $prodB, 8, 8);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $this->postApprove($receipt, [
            'items' => [
                ['product_id' => $prodA->id, 'cantidad' => 6, 'costo_unitario' => 10.00],
                ['product_id' => $prodB->id, 'cantidad' => 8, 'costo_unitario' => 12.00],
            ],
        ]);

        $this->assertDatabaseHas('inventories', ['product_id' => $prodA->id, 'cantidad_stock' => 6]);
        $this->assertDatabaseHas('inventories', ['product_id' => $prodB->id, 'cantidad_stock' => 8]);
    }

    // ── 67. Multi-item PO: PO cambia a recibido ───────────────────────────────

    #[Test]
    public function test_po_multi_item_po_cambia_a_recibido(): void
    {
        $prov  = $this->makePoProvider();
        $po    = $this->makePo($prov);
        $prodA = $this->makeProduct();
        $prodB = $this->makeProduct();
        $this->addPoItem($po, $prodA, 10, 10);
        $this->addPoItem($po, $prodB, 5, 5);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $this->postApprove($receipt, [
            'items' => [
                ['product_id' => $prodA->id, 'cantidad' => 10, 'costo_unitario' => 10.00],
                ['product_id' => $prodB->id, 'cantidad' => 5,  'costo_unitario' => 10.00],
            ],
        ]);

        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'estado' => 'recibido']);
    }

    // ── 68. Multi-item PO: sin CashMovement ──────────────────────────────────

    #[Test]
    public function test_po_multi_item_no_crea_cash_movement(): void
    {
        $prov  = $this->makePoProvider();
        $po    = $this->makePo($prov);
        $prodA = $this->makeProduct();
        $prodB = $this->makeProduct();
        $this->addPoItem($po, $prodA, 5, 5);
        $this->addPoItem($po, $prodB, 5, 5);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $before = CashMovement::count();

        $this->postApprove($receipt, [
            'items' => [
                ['product_id' => $prodA->id, 'cantidad' => 5, 'costo_unitario' => 10.00],
                ['product_id' => $prodB->id, 'cantidad' => 5, 'costo_unitario' => 10.00],
            ],
        ]);

        $this->assertEquals($before, CashMovement::count());
    }

    // ── 69. PO-receipt: sede_id=null requiere override ────────────────────────

    #[Test]
    public function test_po_receipt_sede_null_requiere_override(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede, ['sede_id' => null]);

        $response = $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 10.00]],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('inventory_receipts', ['id' => $receipt->id, 'estado' => 'pendiente']);
    }

    // ── 70. PO-receipt: sede_id_override aplicado correctamente ──────────────

    #[Test]
    public function test_po_receipt_sede_override_aplicado(): void
    {
        $prov     = $this->makePoProvider();
        $po       = $this->makePo($prov);
        $product  = $this->makeProduct();
        $otraSede = Sede::factory()->create();
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede, ['sede_id' => null]);

        $response = $this->postApprove($receipt, [
            'items'            => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 10.00]],
            'sede_id_override' => $otraSede->id,
        ]);

        $response->assertRedirect(route('inventory-receipts.index'));
        $this->assertDatabaseHas('inventory_receipts', ['id' => $receipt->id, 'sede_id' => $otraSede->id]);
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'sede_id' => $otraSede->id]);
    }

    // ── 71. PO-receipt: rollback — PO não muda se transação falha ─────────────

    #[Test]
    public function test_po_receipt_rollback_po_nao_muda_se_falha(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $prodA   = $this->makeProduct();
        $prodB   = $this->makeProduct();
        $this->addPoItem($po, $prodA, 5, 5);
        $this->addPoItem($po, $prodB, 5, 5);
        $this->makeInventory($prodA, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        // Soft-delete prodB: Product::find(B) → null inside transaction → TypeError → rollback
        $prodB->delete();

        $this->postApprove($receipt, [
            'items' => [
                ['product_id' => $prodA->id, 'cantidad' => 5, 'costo_unitario' => 10.00],
                ['product_id' => $prodB->id, 'cantidad' => 5, 'costo_unitario' => 10.00],
            ],
        ]);

        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'estado' => 'enviado']);
    }

    // ── 72. PO-receipt: rollback — stock não muda se transação falha ──────────

    #[Test]
    public function test_po_receipt_rollback_stock_nao_muda_se_falha(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $prodA   = $this->makeProduct();
        $prodB   = $this->makeProduct();
        $this->addPoItem($po, $prodA, 5, 5);
        $this->addPoItem($po, $prodB, 5, 5);
        $this->makeInventory($prodA, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $prodB->delete();

        $this->postApprove($receipt, [
            'items' => [
                ['product_id' => $prodA->id, 'cantidad' => 5, 'costo_unitario' => 10.00],
                ['product_id' => $prodB->id, 'cantidad' => 5, 'costo_unitario' => 10.00],
            ],
        ]);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $prodA->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 10,
        ]);
    }

    // ── 73. PO-receipt: rollback — receipt permanece pendiente ───────────────

    #[Test]
    public function test_po_receipt_rollback_receipt_permanece_pendiente(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $prodA   = $this->makeProduct();
        $prodB   = $this->makeProduct();
        $this->addPoItem($po, $prodA, 5, 5);
        $this->addPoItem($po, $prodB, 5, 5);
        $this->makeInventory($prodA, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $prodB->delete();

        $this->postApprove($receipt, [
            'items' => [
                ['product_id' => $prodA->id, 'cantidad' => 5, 'costo_unitario' => 10.00],
                ['product_id' => $prodB->id, 'cantidad' => 5, 'costo_unitario' => 10.00],
            ],
        ]);

        $this->assertDatabaseHas('inventory_receipts', [
            'id'           => $receipt->id,
            'estado'       => 'pendiente',
            'aprobado_por' => null,
        ]);
    }

    // ── 74. PO-receipt: doble aprobación rechazada ───────────────────────────

    #[Test]
    public function test_po_receipt_doble_aprobacion_rechazada(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $payload = ['items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 10.00]]];

        $this->postApprove($receipt, $payload);

        $response = $this->postApprove($receipt, $payload);
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ── 75. PO-receipt: doble aprobação não duplica stock ────────────────────

    #[Test]
    public function test_po_receipt_doble_aprobacion_no_duplica_stock(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $payload = ['items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 10.00]]];

        $this->postApprove($receipt, $payload);
        $this->postApprove($receipt, $payload);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $this->sede->id,
            'cantidad_stock' => 10,
        ]);
    }

    // ── 76. PO-receipt: rechazo funciona correctamente ────────────────────────

    #[Test]
    public function test_po_receipt_rechazo_funciona(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $response = $this->actingAs($this->approver)->patch(
            route('inventory-receipts.reject', $receipt),
            ['notas_aprobacion' => 'Rechazado por prueba']
        );

        $response->assertRedirect(route('inventory-receipts.index'));
        $this->assertDatabaseHas('inventory_receipts', ['id' => $receipt->id, 'estado' => 'rechazado']);
    }

    // ── 77. PO-receipt: rechazo NO cambia estado de PO ───────────────────────

    #[Test]
    public function test_po_receipt_rechazo_no_cambia_estado_po(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $this->actingAs($this->approver)->patch(
            route('inventory-receipts.reject', $receipt),
            ['notas_aprobacion' => 'Rechazado']
        );

        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'estado' => 'enviado']);
    }

    // ── 78. PO-receipt: notas_aprobacion almacenadas ──────────────────────────

    #[Test]
    public function test_po_receipt_notas_aprobacion_almacenadas(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $this->postApprove($receipt, [
            'items'            => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 10.00]],
            'notas_aprobacion' => 'Revisado en bodega',
        ]);

        $this->assertDatabaseHas('inventory_receipts', [
            'id'               => $receipt->id,
            'notas_aprobacion' => 'Revisado en bodega',
        ]);
    }

    // ── 79. PO-receipt: InventoryReceiptItem creado por cada item ────────────

    #[Test]
    public function test_po_receipt_inventory_receipt_items_creados(): void
    {
        $prov  = $this->makePoProvider();
        $po    = $this->makePo($prov);
        $prodA = $this->makeProduct();
        $prodB = $this->makeProduct();
        $this->addPoItem($po, $prodA, 5, 5);
        $this->addPoItem($po, $prodB, 3, 3);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $this->postApprove($receipt, [
            'items' => [
                ['product_id' => $prodA->id, 'cantidad' => 5, 'costo_unitario' => 10.00],
                ['product_id' => $prodB->id, 'cantidad' => 3, 'costo_unitario' => 20.00],
            ],
        ]);

        $this->assertDatabaseHas('inventory_receipt_items', [
            'receipt_id' => $receipt->id,
            'product_id' => $prodA->id,
            'cantidad'   => 5,
        ]);
        $this->assertDatabaseHas('inventory_receipt_items', [
            'receipt_id' => $receipt->id,
            'product_id' => $prodB->id,
            'cantidad'   => 3,
        ]);
    }

    // ── 80. PO-receipt: precio_compra actualizado (costo promedio) ────────────

    #[Test]
    public function test_po_receipt_precio_compra_actualizado(): void
    {
        // oldStock=10 @ $10, receive 10 @ $20 → avg = (100+200)/20 = $15
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct(5, 10.00);
        $this->makeInventory($product, 10);
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 20.00]],
        ]);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'precio_compra' => 15.00]);
    }

    // ── 81. PO-receipt: alerta desactivada si stock suficiente ───────────────

    #[Test]
    public function test_po_receipt_alerta_desactivada_si_stock_suficiente(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct(5);
        StockAlert::create([
            'product_id'    => $product->id,
            'sede_id'       => $this->sede->id,
            'stock_actual'  => 0,
            'stock_minimo'  => 5,
            'alerta_activa' => true,
            'fecha_alerta'  => now(),
        ]);
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 10.00]],
        ]);

        $this->assertDatabaseHas('stock_alerts', [
            'product_id'    => $product->id,
            'sede_id'       => $this->sede->id,
            'alerta_activa' => false,
        ]);
    }

    // ── 82. Items enviados en orden inverso → resultado correcto ──────────────

    #[Test]
    public function test_po_receipt_items_orden_inverso_resultado_correcto(): void
    {
        $prov  = $this->makePoProvider();
        $po    = $this->makePo($prov);
        $prodA = $this->makeProduct();
        $prodB = $this->makeProduct(); // prodB.id > prodA.id
        $this->addPoItem($po, $prodA, 8, 8);
        $this->addPoItem($po, $prodB, 5, 5);
        $receipt = $this->makePoReceipt($po, $this->sede);

        // Submit reversed: B first, A second
        $this->postApprove($receipt, [
            'items' => [
                ['product_id' => $prodB->id, 'cantidad' => 5, 'costo_unitario' => 10.00],
                ['product_id' => $prodA->id, 'cantidad' => 8, 'costo_unitario' => 10.00],
            ],
        ]);

        $this->assertDatabaseHas('inventories', ['product_id' => $prodA->id, 'cantidad_stock' => 8]);
        $this->assertDatabaseHas('inventories', ['product_id' => $prodB->id, 'cantidad_stock' => 5]);
        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'estado' => 'recibido']);
    }

    // ── 83. Direct receipt con purchase_order_id=null: PO no afectada ─────────

    #[Test]
    public function test_direct_receipt_po_null_no_afecta_ninguna_po(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov); // PO exists but unrelated
        $product = $this->makeProduct();

        // $this->receipt has purchase_order_id=null
        $this->postApprove($this->receipt, $this->defaultPayload($product));

        $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'estado' => 'enviado']);
    }

    // ── 84. PO-receipt: movimiento tiene sede_id correcto ────────────────────

    #[Test]
    public function test_po_receipt_movimiento_tiene_sede_correcto(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 10.00]],
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'sede_id'    => $this->sede->id,
            'tipo'       => 'entrada',
        ]);
    }

    // ── 85. PO-receipt: items_validation_error no deja receipt_items ──────────

    #[Test]
    public function test_po_receipt_validation_error_no_crea_receipt_items(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 7);
        $receipt = $this->makePoReceipt($po, $this->sede);

        // Wrong quantity: 10 instead of 7
        $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 10.00]],
        ]);

        $this->assertEquals(
            0,
            InventoryReceiptItem::where('receipt_id', $receipt->id)->count()
        );
    }

    // ── 86. PO-receipt: validation_error no crea inventory_movements ──────────

    #[Test]
    public function test_po_receipt_validation_error_no_crea_inventory_movements(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 7);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $before = InventoryMovement::count();

        $this->postApprove($receipt, [
            'items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 10.00]],
        ]);

        $this->assertEquals($before, InventoryMovement::count());
    }

    // ── 87. 403 sin permiso — PO-receipt ─────────────────────────────────────

    #[Test]
    public function test_po_receipt_403_sin_permiso(): void
    {
        $prov    = $this->makePoProvider();
        $po      = $this->makePo($prov);
        $product = $this->makeProduct();
        $this->addPoItem($po, $product, 10, 10);
        $receipt = $this->makePoReceipt($po, $this->sede);

        $user = User::factory()->create(); // no permission

        $response = $this->actingAs($user)->post(
            route('inventory-receipts.approve', $receipt),
            ['items' => [['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 10.00]]]
        );

        $response->assertStatus(403);
    }

    // ── 88. PO-receipt: rollback si ActivityLogger falla DESPUÉS de PO→recibido ─
    //
    // Forces ActivityLog::create to throw by truncating description VARCHAR(255)
    // to VARCHAR(1). This is the only DB write that occurs after
    // $lockedOrder->update(['estado' => 'recibido']), so it proves the
    // DB::transaction wraps PO→recibido and rolls it back on any exception.

    #[Test]
    public function test_po_receipt_rollback_po_si_activity_log_falla_post_recibido(): void
    {
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE activity_logs MODIFY COLUMN description VARCHAR(1) NOT NULL'
        );
        \Illuminate\Support\Facades\DB::purge();

        try {
            $prov    = $this->makePoProvider();
            $po      = $this->makePo($prov);
            $product = $this->makeProduct();
            $this->addPoItem($po, $product, 5, 5);
            $receipt = $this->makePoReceipt($po, $this->sede);

            $this->postApprove($receipt, [
                'items' => [['product_id' => $product->id, 'cantidad' => 5, 'costo_unitario' => 10.00]],
            ]);

            // PO must still be 'enviado' — rolled back
            $this->assertDatabaseHas('purchase_orders', ['id' => $po->id, 'estado' => 'enviado']);

            // Receipt must still be pendiente
            $this->assertDatabaseHas('inventory_receipts', ['id' => $receipt->id, 'estado' => 'pendiente']);

            // Stock must not have changed (0 rows or 0 quantity)
            $this->assertEquals(
                0,
                \App\Models\Inventory::where('product_id', $product->id)
                    ->where('sede_id', $this->sede->id)
                    ->sum('cantidad_stock')
            );

            // 0 InventoryReceiptItem
            $this->assertEquals(0, InventoryReceiptItem::where('receipt_id', $receipt->id)->count());

            // 0 CashMovement
            $this->assertEquals(0, CashMovement::where('type', 'pago_proveedor')->count());
        } finally {
            \Illuminate\Support\Facades\DB::statement(
                'ALTER TABLE activity_logs MODIFY COLUMN description VARCHAR(255) NOT NULL'
            );
            \Illuminate\Support\Facades\DB::purge();
        }
    }

    // ── 42. sede_id_override + rollback: sede_id vuelve a null ───────────────
    //
    // This is the critical atomicity proof: before the fix, sede_id was written
    // OUTSIDE the transaction and would permanently stick even on failure.
    // After the fix, the UPDATE is inside the transaction and rolls back.

    #[Test]
    public function test_sede_override_rollback_vuelve_a_null(): void
    {
        \Illuminate\Support\Facades\DB::statement(
            'ALTER TABLE inventory_receipts MODIFY COLUMN sede_id BIGINT UNSIGNED NULL'
        );

        // ALTER TABLE causes MySQL to implicitly COMMIT the test-wrapping transaction,
        // leaving the PDO in auto-commit mode while Laravel's counter stays at 1.
        // Purge the connection so the controller's DB::transaction() issues a real BEGIN,
        // which makes ROLLBACK actually revert the sede_id update.
        \Illuminate\Support\Facades\DB::purge();

        try {
            $this->receipt->update(['sede_id' => null]);

            $otraSede = Sede::factory()->create();
            $productA = $this->makeProduct();
            $productB = $this->makeProduct(); // will be soft-deleted → TypeError mid-transaction
            $this->makeInventory($productA, 10);

            $productB->delete(); // soft-delete; validation still passes

            // A.id < B.id → sorted first; A processes, then B throws
            // 5×10 + 5×10 = 100 = monto_pagado ✓
            $this->postApprove($this->receipt, [
                'items' => [
                    ['product_id' => $productA->id, 'cantidad' => 5, 'costo_unitario' => 10.00],
                    ['product_id' => $productB->id, 'cantidad' => 5, 'costo_unitario' => 10.00],
                ],
                'sede_id_override' => $otraSede->id,
            ]);

            // ── CRITICAL: sede_id must be NULL again (rolled back) ──────────
            $fresh = $this->receipt->fresh();
            $this->assertNull($fresh->sede_id,
                'receipt.sede_id must be NULL — override inside transaction must be rolled back'
            );

            // Receipt remains pendiente
            $this->assertDatabaseHas('inventory_receipts', [
                'id'           => $this->receipt->id,
                'estado'       => 'pendiente',
                'aprobado_por' => null,
                'aprobado_at'  => null,
            ]);

            // Stock A not changed (rolled back)
            $this->assertDatabaseHas('inventories', [
                'product_id'     => $productA->id,
                'cantidad_stock' => 10,
            ]);

            // 0 InventoryReceiptItem persisted
            $this->assertEquals(
                0,
                InventoryReceiptItem::where('receipt_id', $this->receipt->id)->count()
            );

            // 0 InventoryMovement persisted
            $this->assertEquals(
                0,
                InventoryMovement::where('reference_id', $this->receipt->id)
                                 ->where('reference_type', 'receipt')->count()
            );

            // 0 CashMovement from this approval attempt
            $this->assertEquals(
                0,
                CashMovement::where('type', 'pago_proveedor')->count()
            );
        } finally {
            \Illuminate\Support\Facades\DB::table('inventory_receipts')
                ->whereNull('sede_id')
                ->update(['sede_id' => $this->sede->id]);
            \Illuminate\Support\Facades\DB::statement(
                'ALTER TABLE inventory_receipts MODIFY COLUMN sede_id BIGINT UNSIGNED NOT NULL'
            );
        }
    }
    // ── Audit log ─────────────────────────────────────────────────────────────

    #[Test]
    public function test_aprobacion_guarda_estado_sede_y_cambio_real_de_stock_en_auditoria(): void
    {
        $product = $this->makeProduct();
        $this->makeInventory($product, 20);

        $this->postApprove(
            $this->receipt,
            $this->defaultPayload($product, 10)
        );

        $log = \App\Models\ActivityLog::where('action', 'recepcion.aprobada')
            ->latest('id')
            ->firstOrFail();

        $label = $product->nombre . " (#{$product->id})";

        $this->assertSame($this->receipt->id, $log->model_id);
        $this->assertSame($this->sede->id, $log->sede_id);
        $this->assertSame('pendiente', $log->old_values['estado'] ?? null);
        $this->assertSame('aprobado', $log->new_values['estado'] ?? null);
        $this->assertSame(20, $log->old_values[$label] ?? null);
        $this->assertSame(30, $log->new_values[$label] ?? null);
    }

    #[Test]
    public function test_aprobacion_multi_item_guarda_cambio_de_cada_producto_en_auditoria(): void
    {
        $productA = $this->makeProduct();
        $productB = $this->makeProduct();

        $this->makeInventory($productA, 5);
        $this->makeInventory($productB, 8);

        $this->postApprove($this->receipt, [
            'items' => [
                [
                    'product_id' => $productA->id,
                    'cantidad' => 4,
                    'costo_unitario' => 10.00,
                ],
                [
                    'product_id' => $productB->id,
                    'cantidad' => 3,
                    'costo_unitario' => 20.00,
                ],
            ],
        ]);

        $log = \App\Models\ActivityLog::where('action', 'recepcion.aprobada')
            ->latest('id')
            ->firstOrFail();

        $labelA = $productA->nombre . " (#{$productA->id})";
        $labelB = $productB->nombre . " (#{$productB->id})";

        $this->assertSame(5, $log->old_values[$labelA] ?? null);
        $this->assertSame(9, $log->new_values[$labelA] ?? null);
        $this->assertSame(8, $log->old_values[$labelB] ?? null);
        $this->assertSame(11, $log->new_values[$labelB] ?? null);
    }

    #[Test]
    public function test_registro_guarda_datos_utiles_y_sede_en_auditoria(): void
    {
        Permission::firstOrCreate([
            'name' => 'receipts.create',
            'guard_name' => 'web',
        ]);

        $operator = User::factory()->create([
            'sede_id' => $this->sede->id,
        ]);
        $operator->givePermissionTo('receipts.create');

        $provider = Provider::create([
            'nombre' => 'Proveedor Auditoria',
            'activo' => true,
        ]);

        $response = $this->actingAs($operator)->post(
            route('inventory-receipts.store'),
            [
                'provider_id' => $provider->id,
                'monto_pagado' => 150.50,
            ]
        );

        $response->assertRedirect(route('dashboard'));

        $log = \App\Models\ActivityLog::where('action', 'recepcion.registrada')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($this->sede->id, $log->sede_id);
        $this->assertSame('Proveedor Auditoria', $log->new_values['proveedor'] ?? null);
        $this->assertEquals(150.50, $log->new_values['monto_pagado'] ?? null);
        $this->assertSame('pendiente', $log->new_values['estado'] ?? null);
    }

}
