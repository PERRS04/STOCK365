<?php

namespace Tests\Feature;

use App\Models\InventoryReceipt;
use App\Models\Provider;
use App\Models\PurchaseOrder;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryReceiptPurchaseOrderLinkTest extends TestCase
{
    use RefreshDatabase;

    private User         $user;
    private Sede         $sede;
    private Provider     $provider;
    private PurchaseOrder $purchaseOrder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user     = User::factory()->create();
        $this->sede     = Sede::factory()->create();
        $this->provider = Provider::create(['nombre' => 'Proveedor Test', 'activo' => true]);

        $this->purchaseOrder = PurchaseOrder::create([
            'provider_id'  => $this->provider->id,
            'created_by'   => $this->user->id,
            'fecha_pedido' => now()->toDateString(),
            'total'        => 100.00,
            'estado'       => 'pendiente',
        ]);
    }

    private function makeReceipt(array $overrides = []): InventoryReceipt
    {
        return InventoryReceipt::create(array_merge([
            'sede_id'       => $this->sede->id,
            'user_id'       => $this->user->id,
            'provider_id'   => $this->provider->id,
            'supplier_name' => 'Proveedor Test',
            'monto_pagado'  => 50.00,
            'estado'        => 'pendiente',
        ], $overrides));
    }

    // ── 1. purchase_order_id acepta NULL ──────────────────────────────────────

    #[Test]
    public function test_purchase_order_id_acepta_null(): void
    {
        $receipt = $this->makeReceipt(['purchase_order_id' => null]);

        $this->assertNull($receipt->purchase_order_id);
        $this->assertDatabaseHas('inventory_receipts', [
            'id'                => $receipt->id,
            'purchase_order_id' => null,
        ]);
    }

    // ── 2. purchase_order_id acepta PO válida ─────────────────────────────────

    #[Test]
    public function test_purchase_order_id_acepta_po_valida(): void
    {
        $receipt = $this->makeReceipt(['purchase_order_id' => $this->purchaseOrder->id]);

        $this->assertEquals($this->purchaseOrder->id, $receipt->purchase_order_id);
        $this->assertDatabaseHas('inventory_receipts', [
            'id'                => $receipt->id,
            'purchase_order_id' => $this->purchaseOrder->id,
        ]);
    }

    // ── 3. FK rechaza purchase_order_id inexistente ───────────────────────────

    #[Test]
    public function test_fk_rechaza_purchase_order_id_inexistente(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        // Bypass Eloquent to test the raw FK constraint at DB level
        DB::table('inventory_receipts')->insert([
            'purchase_order_id' => 99999,
            'sede_id'           => $this->sede->id,
            'user_id'           => $this->user->id,
            'supplier_name'     => 'Test',
            'monto_pagado'      => 50.00,
            'estado'            => 'pendiente',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }

    // ── 4. ON DELETE SET NULL: borrar PO → receipt.purchase_order_id = NULL ──

    #[Test]
    public function test_borrar_po_establece_purchase_order_id_null_en_receipt(): void
    {
        $receipt = $this->makeReceipt(['purchase_order_id' => $this->purchaseOrder->id]);

        $this->assertEquals($this->purchaseOrder->id, $receipt->purchase_order_id);

        $this->purchaseOrder->delete();

        $this->assertNull($receipt->fresh()->purchase_order_id);
        $this->assertDatabaseHas('inventory_receipts', [
            'id'                => $receipt->id,
            'purchase_order_id' => null,
        ]);
    }

    // ── 5. Una PO puede tener más de una receipt (sin UNIQUE) ─────────────────

    #[Test]
    public function test_po_puede_tener_multiples_receipts(): void
    {
        $r1 = $this->makeReceipt(['purchase_order_id' => $this->purchaseOrder->id]);
        $r2 = $this->makeReceipt(['purchase_order_id' => $this->purchaseOrder->id]);
        $r3 = $this->makeReceipt(['purchase_order_id' => $this->purchaseOrder->id]);

        $count = InventoryReceipt::where('purchase_order_id', $this->purchaseOrder->id)->count();
        $this->assertEquals(3, $count);
    }

    // ── 6. sede_id acepta NULL ────────────────────────────────────────────────

    #[Test]
    public function test_sede_id_acepta_null(): void
    {
        $receipt = $this->makeReceipt(['sede_id' => null]);

        $this->assertNull($receipt->sede_id);
        $this->assertDatabaseHas('inventory_receipts', [
            'id'      => $receipt->id,
            'sede_id' => null,
        ]);
    }

    // ── 7. Receipt existente con sede continúa funcionando ────────────────────

    #[Test]
    public function test_receipt_existente_con_sede_continua_funcionando(): void
    {
        $receipt = $this->makeReceipt(['sede_id' => $this->sede->id]);

        $this->assertEquals($this->sede->id, $receipt->sede_id);
        $this->assertEquals($this->sede->id, $receipt->fresh()->sede_id);
        $this->assertDatabaseHas('inventory_receipts', [
            'id'      => $receipt->id,
            'sede_id' => $this->sede->id,
        ]);
    }

    // ── 8. Receipt directa: purchase_order_id=null, sede_id válida ───────────

    #[Test]
    public function test_receipt_directa_sin_po(): void
    {
        $receipt = $this->makeReceipt([
            'purchase_order_id' => null,
            'sede_id'           => $this->sede->id,
        ]);

        $this->assertNull($receipt->purchase_order_id);
        $this->assertEquals($this->sede->id, $receipt->sede_id);
        $this->assertDatabaseHas('inventory_receipts', [
            'id'                => $receipt->id,
            'purchase_order_id' => null,
            'sede_id'           => $this->sede->id,
        ]);
    }

    // ── 9. $receipt->purchaseOrder relación ───────────────────────────────────

    #[Test]
    public function test_receipt_belongs_to_purchase_order(): void
    {
        $receipt = $this->makeReceipt(['purchase_order_id' => $this->purchaseOrder->id]);

        $po = $receipt->purchaseOrder;

        $this->assertInstanceOf(PurchaseOrder::class, $po);
        $this->assertEquals($this->purchaseOrder->id, $po->id);
        $this->assertEquals($this->purchaseOrder->total, $po->total);
    }

    // ── Receipt sin PO → relación devuelve null ───────────────────────────────

    #[Test]
    public function test_receipt_sin_po_devuelve_null_en_relacion(): void
    {
        $receipt = $this->makeReceipt(['purchase_order_id' => null]);

        $this->assertNull($receipt->purchaseOrder);
    }

    // ── 10. $order->receipts relación ─────────────────────────────────────────

    #[Test]
    public function test_purchase_order_has_many_receipts(): void
    {
        $this->makeReceipt(['purchase_order_id' => $this->purchaseOrder->id]);
        $this->makeReceipt(['purchase_order_id' => $this->purchaseOrder->id]);

        $receipts = $this->purchaseOrder->receipts;

        $this->assertCount(2, $receipts);
        $this->assertContainsOnlyInstancesOf(InventoryReceipt::class, $receipts);
    }

    // ── PO sin receipts → colección vacía ────────────────────────────────────

    #[Test]
    public function test_po_sin_receipts_devuelve_coleccion_vacia(): void
    {
        $this->assertCount(0, $this->purchaseOrder->receipts);
    }

    // ── 11. Múltiples receipts pertenecen correctamente a la misma PO ─────────

    #[Test]
    public function test_multiples_receipts_pertenecen_a_la_misma_po(): void
    {
        $r1 = $this->makeReceipt(['purchase_order_id' => $this->purchaseOrder->id]);
        $r2 = $this->makeReceipt(['purchase_order_id' => $this->purchaseOrder->id]);

        // Inverse: each receipt points back to the same PO
        foreach ([$r1, $r2] as $receipt) {
            $this->assertEquals($this->purchaseOrder->id, $receipt->purchaseOrder->id);
        }

        // Forward: PO sees both receipts
        $ids = $this->purchaseOrder->receipts->pluck('id');
        $this->assertTrue($ids->contains($r1->id));
        $this->assertTrue($ids->contains($r2->id));
    }

    // ── Receipts de distintas POs no se mezclan ───────────────────────────────

    #[Test]
    public function test_receipts_de_distintas_pos_no_se_mezclan(): void
    {
        $otraPO = PurchaseOrder::create([
            'provider_id'  => $this->provider->id,
            'created_by'   => $this->user->id,
            'fecha_pedido' => now()->toDateString(),
            'total'        => 200.00,
            'estado'       => 'enviado',
        ]);

        $r1 = $this->makeReceipt(['purchase_order_id' => $this->purchaseOrder->id]);
        $r2 = $this->makeReceipt(['purchase_order_id' => $otraPO->id]);

        $this->assertCount(1, $this->purchaseOrder->receipts);
        $this->assertCount(1, $otraPO->receipts);
        $this->assertEquals($r1->id, $this->purchaseOrder->receipts->first()->id);
        $this->assertEquals($r2->id, $otraPO->receipts->first()->id);
    }

    // ── Receipt con sede=null y PO válida (caso boss sin sede) ───────────────

    #[Test]
    public function test_receipt_con_sede_null_y_po_valida(): void
    {
        $receipt = $this->makeReceipt([
            'purchase_order_id' => $this->purchaseOrder->id,
            'sede_id'           => null,
        ]);

        $this->assertNull($receipt->sede_id);
        $this->assertEquals($this->purchaseOrder->id, $receipt->purchase_order_id);
        $this->assertInstanceOf(PurchaseOrder::class, $receipt->purchaseOrder);
    }
}
