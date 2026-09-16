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
}
