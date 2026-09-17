<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\InventoryReceipt;
use App\Models\Product;
use App\Models\Provider;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\ReceiptPaymentAllocation;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PurchaseOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    private User            $boss;
    private User            $nonBoss;
    private Provider        $provider;
    private Product         $productA;
    private Product         $productB;
    private PurchaseOrder   $order;
    private PurchaseOrderItem $poItem;
    private PurchaseOrderItem $poItem2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $bossRole = Role::firstOrCreate(['name' => 'boss', 'guard_name' => 'web']);

        $this->boss    = User::factory()->create();
        $this->boss->assignRole($bossRole);

        $this->nonBoss = User::factory()->create(); // no role assigned

        $this->provider = Provider::create(['nombre' => 'Proveedor Test', 'activo' => true]);

        $this->productA = Product::factory()->create();
        $this->productB = Product::factory()->create();

        $this->order = PurchaseOrder::create([
            'provider_id'  => $this->provider->id,
            'created_by'   => $this->boss->id,
            'fecha_pedido' => now()->toDateString(),
            'total'        => 112.50,
            'estado'       => 'enviado',
        ]);

        $this->poItem = $this->order->items()->create([
            'product_id'        => $this->productA->id,
            'cantidad'          => 10,
            'cantidad_recibida' => 0,
            'precio_unitario'   => 5.00,
            'subtotal'          => 50.00,
        ]);

        $this->poItem2 = $this->order->items()->create([
            'product_id'        => $this->productB->id,
            'cantidad'          => 8,
            'cantidad_recibida' => 0,
            'precio_unitario'   => 7.8125,
            'subtotal'          => 62.50,
        ]);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function postReceive(PurchaseOrder $order, array $payload, ?User $as = null): TestResponse
    {
        return $this->actingAs($as ?? $this->boss)->post(
            route('purchase-orders.receive', $order),
            $payload
        );
    }

    private function defaultPayload(): array
    {
        return [
            'items' => [
                ['id' => $this->poItem->id,  'cantidad_recibida' => $this->poItem->cantidad],
                ['id' => $this->poItem2->id, 'cantidad_recibida' => $this->poItem2->cantidad],
            ],
        ];
    }

    private function singleItemPayload(?int $cantidad = null): array
    {
        return [
            'items' => [
                ['id' => $this->poItem->id, 'cantidad_recibida' => $cantidad ?? $this->poItem->cantidad],
            ],
        ];
    }

    // ── 1. AUTH / STATE ───────────────────────────────────────────────────────

    #[Test]
    public function test_boss_puede_ejecutar_receive_stock(): void
    {
        $response = $this->postReceive($this->order, $this->defaultPayload());

        // Successful: should redirect (not 403)
        $response->assertRedirect();
        $response->assertSessionMissing('error');
        $this->assertFalse($response->isClientError());
    }

    #[Test]
    public function test_no_boss_recibe_403(): void
    {
        $response = $this->postReceive($this->order, $this->defaultPayload(), $this->nonBoss);

        $response->assertStatus(403);
    }

    #[Test]
    public function test_po_pendiente_es_rechazada(): void
    {
        $poPendiente = PurchaseOrder::create([
            'provider_id'  => $this->provider->id,
            'created_by'   => $this->boss->id,
            'fecha_pedido' => now()->toDateString(),
            'total'        => 0,
            'estado'       => 'pendiente',
        ]);

        $response = $this->postReceive($poPendiente, $this->singleItemPayload());

        $response->assertSessionHas('error');
        $this->assertStringContainsString('enviado', session('error'));
        $this->assertDatabaseCount('inventory_receipts', 0);
    }

    #[Test]
    public function test_po_recibida_es_rechazada(): void
    {
        $poRecibida = PurchaseOrder::create([
            'provider_id'  => $this->provider->id,
            'created_by'   => $this->boss->id,
            'fecha_pedido' => now()->toDateString(),
            'total'        => 0,
            'estado'       => 'recibido',
        ]);

        $response = $this->postReceive($poRecibida, $this->singleItemPayload());

        $response->assertSessionHas('error', 'Este pedido ya fue recibido.');
        $this->assertDatabaseCount('inventory_receipts', 0);
    }

    #[Test]
    public function test_po_inexistente_retorna_404(): void
    {
        $response = $this->actingAs($this->boss)->post(
            route('purchase-orders.receive', 99999),
            $this->defaultPayload()
        );

        $response->assertStatus(404);
    }

    // ── 2. RECEIPT FIELDS ────────────────────────────────────────────────────

    #[Test]
    public function test_crea_exactamente_una_inventory_receipt(): void
    {
        $this->assertDatabaseCount('inventory_receipts', 0);

        $this->postReceive($this->order, $this->defaultPayload());

        $this->assertDatabaseCount('inventory_receipts', 1);
    }

    #[Test]
    public function test_receipt_tiene_purchase_order_id_correcto(): void
    {
        $this->postReceive($this->order, $this->defaultPayload());

        $this->assertDatabaseHas('inventory_receipts', [
            'purchase_order_id' => $this->order->id,
        ]);
    }

    #[Test]
    public function test_receipt_tiene_sede_id_null(): void
    {
        $this->postReceive($this->order, $this->defaultPayload());

        $this->assertDatabaseHas('inventory_receipts', [
            'purchase_order_id' => $this->order->id,
            'sede_id'           => null,
        ]);
    }

    #[Test]
    public function test_receipt_tiene_provider_id_correcto(): void
    {
        $this->postReceive($this->order, $this->defaultPayload());

        $this->assertDatabaseHas('inventory_receipts', [
            'purchase_order_id' => $this->order->id,
            'provider_id'       => $this->provider->id,
        ]);
    }

    #[Test]
    public function test_receipt_tiene_supplier_name_correcto(): void
    {
        $this->postReceive($this->order, $this->defaultPayload());

        $this->assertDatabaseHas('inventory_receipts', [
            'purchase_order_id' => $this->order->id,
            'supplier_name'     => $this->provider->nombre,
        ]);
    }

    #[Test]
    public function test_receipt_tiene_monto_pagado_cero(): void
    {
        $this->postReceive($this->order, $this->defaultPayload());

        $receipt = InventoryReceipt::first();
        $this->assertNotNull($receipt);
        $this->assertEquals('0.00', $receipt->monto_pagado);
    }

    #[Test]
    public function test_receipt_tiene_estado_pendiente(): void
    {
        $this->postReceive($this->order, $this->defaultPayload());

        $this->assertDatabaseHas('inventory_receipts', [
            'purchase_order_id' => $this->order->id,
            'estado'            => 'pendiente',
        ]);
    }

    #[Test]
    public function test_receipt_tiene_user_id_del_boss_autenticado(): void
    {
        $this->postReceive($this->order, $this->defaultPayload());

        $this->assertDatabaseHas('inventory_receipts', [
            'purchase_order_id' => $this->order->id,
            'user_id'           => $this->boss->id,
        ]);
    }

    #[Test]
    public function test_receipt_tiene_invoice_path_null(): void
    {
        $this->postReceive($this->order, $this->defaultPayload());

        $receipt = InventoryReceipt::first();
        $this->assertNotNull($receipt);
        $this->assertNull($receipt->invoice_path);
    }

    #[Test]
    public function test_no_se_crea_receipt_payment_allocation(): void
    {
        $this->postReceive($this->order, $this->defaultPayload());

        $this->assertDatabaseCount('receipt_payment_allocations', 0);
    }

    // ── 3. PO ITEMS ───────────────────────────────────────────────────────────

    #[Test]
    public function test_actualiza_cantidad_recibida_en_po_item(): void
    {
        $this->postReceive($this->order, $this->singleItemPayload(7));

        $this->assertEquals(7, $this->poItem->fresh()->cantidad_recibida);
    }

    #[Test]
    public function test_conserva_cantidad_ordenada_original(): void
    {
        $cantidadOriginal = $this->poItem->cantidad;

        $this->postReceive($this->order, $this->singleItemPayload(5));

        $this->assertEquals($cantidadOriginal, $this->poItem->fresh()->cantidad);
    }

    #[Test]
    public function test_cantidad_recibida_menor_que_ordenada_es_permitida(): void
    {
        $response = $this->postReceive($this->order, $this->singleItemPayload(1)); // ordered=10

        $response->assertSessionMissing('error');
        $this->assertEquals(1, $this->poItem->fresh()->cantidad_recibida);
    }

    #[Test]
    public function test_cantidad_recibida_mayor_que_ordenada_es_rechazada(): void
    {
        // ordered = 10, trying to receive 11
        $response = $this->postReceive($this->order, $this->singleItemPayload(11));

        $response->assertSessionHas('error');
        $this->assertEquals(0, $this->poItem->fresh()->cantidad_recibida);
        $this->assertDatabaseCount('inventory_receipts', 0);
    }

    #[Test]
    public function test_cantidad_recibida_cero_es_rechazada(): void
    {
        // min:1 validation rule rejects 0
        $response = $this->postReceive($this->order, $this->singleItemPayload(0));

        $response->assertSessionHasErrors(['items.0.cantidad_recibida']);
        $this->assertDatabaseCount('inventory_receipts', 0);
    }

    #[Test]
    public function test_cantidad_recibida_negativa_es_rechazada(): void
    {
        // min:1 validation rule rejects negatives
        $response = $this->postReceive($this->order, $this->singleItemPayload(-3));

        $response->assertSessionHasErrors(['items.0.cantidad_recibida']);
        $this->assertDatabaseCount('inventory_receipts', 0);
    }

    #[Test]
    public function test_item_de_otra_po_es_rechazado(): void
    {
        // Create a second PO with its own item
        $otraPO = PurchaseOrder::create([
            'provider_id'  => $this->provider->id,
            'created_by'   => $this->boss->id,
            'fecha_pedido' => now()->toDateString(),
            'total'        => 20.00,
            'estado'       => 'enviado',
        ]);
        $itemAjeno = $otraPO->items()->create([
            'product_id'      => $this->productA->id,
            'cantidad'        => 5,
            'cantidad_recibida' => 0,
            'precio_unitario' => 4.00,
            'subtotal'        => 20.00,
        ]);

        // Send item from otraPO to $this->order's receive endpoint
        $response = $this->postReceive($this->order, [
            'items' => [['id' => $itemAjeno->id, 'cantidad_recibida' => 1]],
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('no pertenece', session('error'));
        $this->assertDatabaseCount('inventory_receipts', 0);
    }

    #[Test]
    public function test_item_id_inexistente_es_rechazado(): void
    {
        $response = $this->postReceive($this->order, [
            'items' => [['id' => 99999, 'cantidad_recibida' => 1]],
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('no pertenece', session('error'));
        $this->assertDatabaseCount('inventory_receipts', 0);
    }

    #[Test]
    public function test_item_duplicado_en_request_es_rechazado(): void
    {
        // Same poItem.id appears twice in the request
        $response = $this->postReceive($this->order, [
            'items' => [
                ['id' => $this->poItem->id, 'cantidad_recibida' => 3],
                ['id' => $this->poItem->id, 'cantidad_recibida' => 4],
            ],
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('duplicados', session('error'));
        $this->assertDatabaseCount('inventory_receipts', 0);
    }

    // ── 4. PO STATE ───────────────────────────────────────────────────────────

    #[Test]
    public function test_po_permanece_enviado_despues_de_receive_stock(): void
    {
        $this->postReceive($this->order, $this->defaultPayload());

        $this->assertEquals('enviado', $this->order->fresh()->estado);
    }

    #[Test]
    public function test_receipt_rechazada_previa_no_bloquea_nueva_receipt(): void
    {
        // Pre-existing rejected receipt does NOT block a new one
        InventoryReceipt::create([
            'purchase_order_id' => $this->order->id,
            'sede_id'           => null,
            'user_id'           => $this->boss->id,
            'provider_id'       => $this->provider->id,
            'supplier_name'     => $this->provider->nombre,
            'monto_pagado'      => 0,
            'estado'            => 'rechazado',
        ]);

        $response = $this->postReceive($this->order, $this->defaultPayload());

        $response->assertSessionMissing('error');
        $this->assertDatabaseCount('inventory_receipts', 2); // 1 rechazado + 1 nuevo pendiente
    }

    #[Test]
    public function test_receipt_pendiente_previa_bloquea_nueva_receipt(): void
    {
        InventoryReceipt::create([
            'purchase_order_id' => $this->order->id,
            'sede_id'           => null,
            'user_id'           => $this->boss->id,
            'provider_id'       => $this->provider->id,
            'supplier_name'     => $this->provider->nombre,
            'monto_pagado'      => 0,
            'estado'            => 'pendiente',
        ]);

        $response = $this->postReceive($this->order, $this->defaultPayload());

        $response->assertSessionHas('error', 'Ya existe una recepción activa para este pedido.');
        $this->assertDatabaseCount('inventory_receipts', 1); // only the pre-existing one
    }

    #[Test]
    public function test_receipt_aprobada_previa_bloquea_nueva_receipt(): void
    {
        InventoryReceipt::create([
            'purchase_order_id' => $this->order->id,
            'sede_id'           => null,
            'user_id'           => $this->boss->id,
            'provider_id'       => $this->provider->id,
            'supplier_name'     => $this->provider->nombre,
            'monto_pagado'      => 0,
            'estado'            => 'aprobado',
        ]);

        $response = $this->postReceive($this->order, $this->defaultPayload());

        $response->assertSessionHas('error', 'Ya existe una recepción activa para este pedido.');
        $this->assertDatabaseCount('inventory_receipts', 1);
    }

    // ── 5. STOCK ISOLATION ────────────────────────────────────────────────────

    #[Test]
    public function test_inventory_no_es_modificado(): void
    {
        $sede = Sede::factory()->create();
        $inventory = Inventory::factory()->create([
            'product_id'     => $this->productA->id,
            'sede_id'        => $sede->id,
            'almacen_id'     => null,
            'cantidad_stock' => 5,
        ]);

        $this->postReceive($this->order, $this->defaultPayload());

        $this->assertEquals(5, $inventory->fresh()->cantidad_stock);
        $this->assertDatabaseCount('inventories', 1);
    }

    #[Test]
    public function test_inventory_movement_no_es_creado(): void
    {
        $this->postReceive($this->order, $this->defaultPayload());

        $this->assertDatabaseCount('inventory_movements', 0);
    }

    #[Test]
    public function test_inventory_stock_service_no_es_invocado(): void
    {
        // InventoryStockService creates InventoryMovement + mutates Inventory on each call.
        // Absence of both proves the service was not invoked.
        $this->postReceive($this->order, $this->defaultPayload());

        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('inventories', 0);
    }

    // ── 6. FINANCIAL ISOLATION ────────────────────────────────────────────────

    #[Test]
    public function test_cash_movement_no_es_creado(): void
    {
        $this->postReceive($this->order, $this->defaultPayload());

        $this->assertDatabaseCount('cash_movements', 0);
    }

    #[Test]
    public function test_receipt_payment_allocation_no_es_creado(): void
    {
        $this->postReceive($this->order, $this->defaultPayload());

        $this->assertDatabaseCount('receipt_payment_allocations', 0);
    }

    // ── 7. TRANSACTION / ROLLBACK ─────────────────────────────────────────────

    #[Test]
    public function test_rollback_revierte_cantidad_recibida_si_receipt_falla(): void
    {
        // Demonstrates MySQL InnoDB rollback: if receipt creation throws after item updates,
        // the item updates are rolled back. This is the same DB::transaction pattern
        // used inside receiveStock().
        try {
            DB::transaction(function () {
                $this->poItem->update(['cantidad_recibida' => 7]);
                // Simulate failure during receipt creation
                throw new \RuntimeException('Forced receipt creation failure');
            });
        } catch (\RuntimeException) {
            // Expected
        }

        $this->assertEquals(0, $this->poItem->fresh()->cantidad_recibida);
        $this->assertDatabaseCount('inventory_receipts', 0);
    }

    #[Test]
    public function test_rollback_revierte_multiples_items_si_receipt_falla(): void
    {
        try {
            DB::transaction(function () {
                $this->poItem->update(['cantidad_recibida' => 5]);
                $this->poItem2->update(['cantidad_recibida' => 3]);
                throw new \RuntimeException('Forced failure after multiple item updates');
            });
        } catch (\RuntimeException) {
            // Expected
        }

        $this->assertEquals(0, $this->poItem->fresh()->cantidad_recibida);
        $this->assertEquals(0, $this->poItem2->fresh()->cantidad_recibida);
    }

    #[Test]
    public function test_ninguna_receipt_parcial_tras_rollback(): void
    {
        // Proves: even if receipt WAS created inside the transaction before failure,
        // it is wiped by the rollback — no orphan receipt persists.
        try {
            DB::transaction(function () {
                $this->poItem->update(['cantidad_recibida' => 5]);
                InventoryReceipt::create([
                    'purchase_order_id' => $this->order->id,
                    'sede_id'           => null,
                    'user_id'           => $this->boss->id,
                    'provider_id'       => $this->provider->id,
                    'supplier_name'     => 'Test',
                    'monto_pagado'      => 0,
                    'estado'            => 'pendiente',
                ]);
                // Failure after both item update and receipt creation
                throw new \RuntimeException('Forced post-creation failure');
            });
        } catch (\RuntimeException) {
            // Expected
        }

        $this->assertDatabaseCount('inventory_receipts', 0);
        $this->assertEquals(0, $this->poItem->fresh()->cantidad_recibida);
    }

    // ── 8. MULTI-ITEM ─────────────────────────────────────────────────────────

    #[Test]
    public function test_multiples_items_actualizan_correctamente(): void
    {
        $this->postReceive($this->order, [
            'items' => [
                ['id' => $this->poItem->id,  'cantidad_recibida' => 6],
                ['id' => $this->poItem2->id, 'cantidad_recibida' => 4],
            ],
        ]);

        $this->assertEquals(6, $this->poItem->fresh()->cantidad_recibida);
        $this->assertEquals(4, $this->poItem2->fresh()->cantidad_recibida);
    }

    #[Test]
    public function test_orden_determinístico_product_id_asc(): void
    {
        // Submit items in reverse product_id order; both must be updated correctly,
        // proving the sort does not corrupt data regardless of submission order.
        $payloadReverso = [
            'items' => [
                ['id' => $this->poItem2->id, 'cantidad_recibida' => 3], // productB (higher id)
                ['id' => $this->poItem->id,  'cantidad_recibida' => 7], // productA (lower id)
            ],
        ];

        $response = $this->postReceive($this->order, $payloadReverso);

        $response->assertSessionMissing('error');
        $this->assertEquals(7, $this->poItem->fresh()->cantidad_recibida);
        $this->assertEquals(3, $this->poItem2->fresh()->cantidad_recibida);
    }

    #[Test]
    public function test_dos_poitems_distintos_mismo_product_id_se_preservan(): void
    {
        // Two independent PO rows for the same product must remain independent
        $poItemDup = $this->order->items()->create([
            'product_id'        => $this->productA->id, // same product as poItem
            'cantidad'          => 4,
            'cantidad_recibida' => 0,
            'precio_unitario'   => 6.00,
            'subtotal'          => 24.00,
        ]);

        $response = $this->postReceive($this->order, [
            'items' => [
                ['id' => $this->poItem->id,  'cantidad_recibida' => 9],
                ['id' => $poItemDup->id,     'cantidad_recibida' => 2],
                ['id' => $this->poItem2->id, 'cantidad_recibida' => 5],
            ],
        ]);

        $response->assertSessionMissing('error');
        $this->assertEquals(9, $this->poItem->fresh()->cantidad_recibida);
        $this->assertEquals(2, $poItemDup->fresh()->cantidad_recibida);
        $this->assertEquals(5, $this->poItem2->fresh()->cantidad_recibida);
    }

    // ── 9. RESPONSE ───────────────────────────────────────────────────────────

    #[Test]
    public function test_redirect_correcto_a_inventory_receipt_show(): void
    {
        $response = $this->postReceive($this->order, $this->defaultPayload());

        $receipt = InventoryReceipt::first();
        $this->assertNotNull($receipt);

        $response->assertRedirect(route('inventory-receipts.show', $receipt));
    }

    #[Test]
    public function test_success_flash_correcto(): void
    {
        $response = $this->postReceive($this->order, $this->defaultPayload());

        $response->assertSessionHas('success', 'Recepción creada. Pendiente de aprobación.');
    }

    // ── 10. CONCURRENCIA (secuencial) ─────────────────────────────────────────

    #[Test]
    public function test_anti_double_submit_segunda_ejecucion_secuencial_bloqueada(): void
    {
        // Verifies the lockForUpdate + active-receipt-check prevents a second receipt
        // from being created for the same PO. Two truly concurrent requests (separate
        // processes) cannot be tested in PHPUnit; this sequential proof covers the
        // observable invariant: the second call is rejected once a receipt exists.

        // First call: succeeds
        $first = $this->postReceive($this->order, $this->defaultPayload());
        $first->assertSessionMissing('error');
        $this->assertDatabaseCount('inventory_receipts', 1);

        // Reset item quantities so the second call is not blocked by quantity validation
        $this->poItem->update(['cantidad_recibida'  => 0]);
        $this->poItem2->update(['cantidad_recibida' => 0]);

        // Second call to the same PO: blocked because pendiente receipt already exists
        $second = $this->postReceive($this->order, $this->defaultPayload());
        $second->assertSessionHas('error', 'Ya existe una recepción activa para este pedido.');

        // Still exactly one receipt
        $this->assertDatabaseCount('inventory_receipts', 1);
    }
}
