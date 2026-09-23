<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SaleTicketTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \App\Http\Middleware\EnsureCashSessionOpen::class,
        ]);

        Permission::firstOrCreate(['name' => 'sales.create',   'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'sales.view.own', 'guard_name' => 'web']);

        $boss = Role::firstOrCreate(['name' => 'boss', 'guard_name' => 'web']);
        $boss->syncPermissions(Permission::all());

        $supervisor = Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        $supervisor->givePermissionTo('sales.view.own');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeBoss(): User
    {
        $u = User::factory()->create(['role' => 'boss', 'sede_id' => null]);
        $u->assignRole('boss');
        return $u;
    }

    private function makeSupervisor(Sede $sede): User
    {
        $u = User::factory()->create(['role' => 'supervisor', 'sede_id' => $sede->id]);
        $u->assignRole('supervisor');
        return $u;
    }

    private function makeOperador(Sede $sede): User
    {
        $operadorRole = Role::firstOrCreate(['name' => 'operador', 'guard_name' => 'web']);
        $operadorRole->givePermissionTo(['sales.create', 'sales.view.own']);

        $u = User::factory()->create(['role' => 'operador', 'sede_id' => $sede->id]);
        $u->assignRole('operador');
        return $u;
    }

    private function makeSale(Sede $sede, User $user, array $overrides = []): Sale
    {
        return Sale::create(array_merge([
            'sede_id'       => $sede->id,
            'user_id'       => $user->id,
            'total_sistema' => 10.00,
            'descuento'     => 0.00,
            'estado'        => 'completada',
            'fecha_venta'   => now(),
        ], $overrides));
    }

    private function makeLegacyItem(Sale $sale, Product $product, array $overrides = []): SaleItem
    {
        return SaleItem::create(array_merge([
            'sale_id'                 => $sale->id,
            'product_id'              => $product->id,
            'presentation_id'         => null,
            'presentation_name'       => null,
            'presentation_factor'     => null,
            'cantidad_presentaciones' => null,
            'cantidad'                => 3,
            'precio_unitario'         => 5.00,
            'costo_unitario'          => 2.00,
            'subtotal'                => 15.00,
        ], $overrides));
    }

    private function makePresItem(
        Sale $sale,
        Product $product,
        string $presName,
        int $factor,
        int $qty,
        float $unitPrice,
        array $overrides = []
    ): SaleItem {
        return SaleItem::create(array_merge([
            'sale_id'                 => $sale->id,
            'product_id'              => $product->id,
            'presentation_id'         => null,
            'presentation_name'       => $presName,
            'presentation_factor'     => $factor,
            'cantidad_presentaciones' => $qty,
            'cantidad'                => $qty * $factor,
            'precio_unitario'         => $unitPrice,
            'costo_unitario'          => 1.00,
            'subtotal'                => $qty * $unitPrice,
        ], $overrides));
    }

    // ── 1. Access control — Boss ──────────────────────────────────────────────

    #[Test]
    public function test_boss_can_view_ticket(): void
    {
        $sede  = Sede::factory()->create();
        $boss  = $this->makeBoss();
        $sale  = $this->makeSale($sede, $boss);

        $response = $this->actingAs($boss)->get(route('sales.ticket', $sale));

        $response->assertStatus(200);
    }

    // ── 2. Access control — Supervisor ───────────────────────────────────────

    #[Test]
    public function test_supervisor_can_view_ticket(): void
    {
        $sede  = Sede::factory()->create();
        $boss  = $this->makeBoss();
        $sup   = $this->makeSupervisor($sede);
        $sale  = $this->makeSale($sede, $boss);

        $response = $this->actingAs($sup)->get(route('sales.ticket', $sale));

        $response->assertStatus(200);
    }

    // ── 3. Access control — Operator own sede ────────────────────────────────

    #[Test]
    public function test_operator_can_view_ticket_from_own_sede(): void
    {
        $sede  = Sede::factory()->create();
        $op    = $this->makeOperador($sede);
        $sale  = $this->makeSale($sede, $op);

        $response = $this->actingAs($op)->get(route('sales.ticket', $sale));

        $response->assertStatus(200);
    }

    // ── 4. IDOR: Operator cannot view ticket from a different sede ────────────

    #[Test]
    public function test_operator_cannot_view_ticket_from_other_sede(): void
    {
        $sedeA = Sede::factory()->create();
        $sedeB = Sede::factory()->create();
        $opA   = $this->makeOperador($sedeA);
        $opB   = $this->makeOperador($sedeB);
        $sale  = $this->makeSale($sedeB, $opB); // sale belongs to sedeB

        // opA (sedeA) tries to access sedeB's sale
        $response = $this->actingAs($opA)->get(route('sales.ticket', $sale));

        $response->assertStatus(403);
    }

    // ── 5. Guest is redirected ────────────────────────────────────────────────

    #[Test]
    public function test_guest_is_redirected_from_ticket(): void
    {
        $sede = Sede::factory()->create();
        $boss = $this->makeBoss();
        $sale = $this->makeSale($sede, $boss);

        $response = $this->get(route('sales.ticket', $sale));

        $response->assertRedirect(route('login'));
    }

    // ── 6. Legacy sale (no presentation) ─────────────────────────────────────

    #[Test]
    public function test_ticket_legacy_sale_no_presentation(): void
    {
        $sede    = Sede::factory()->create();
        $boss    = $this->makeBoss();
        $product = Product::factory()->create(['nombre' => 'Club Verde', 'activo' => true]);
        $sale    = $this->makeSale($sede, $boss, ['total_sistema' => 15.00]);
        $this->makeLegacyItem($sale, $product, ['cantidad' => 3, 'precio_unitario' => 5.00, 'subtotal' => 15.00]);

        $response = $this->actingAs($boss)->get(route('sales.ticket', $sale));

        $response->assertStatus(200);
        $response->assertSee('Club Verde', false);
        $response->assertSee('3', false);        // cantidad base
        $response->assertSee('5.00', false);     // precio_unitario snapshot
    }

    // ── 7. Presentation: Unidad ───────────────────────────────────────────────

    #[Test]
    public function test_ticket_presentation_unidad(): void
    {
        $sede    = Sede::factory()->create();
        $boss    = $this->makeBoss();
        $product = Product::factory()->create(['nombre' => 'Club Azul', 'activo' => true]);
        $sale    = $this->makeSale($sede, $boss, ['total_sistema' => 2.50]);
        $this->makePresItem($sale, $product, 'Unidad', 1, 2, 1.25);

        $response = $this->actingAs($boss)->get(route('sales.ticket', $sale));

        $response->assertStatus(200);
        $response->assertSee('Unidad', false);
        $response->assertSee('2', false);
    }

    // ── 8. Presentation: Six ─────────────────────────────────────────────────

    #[Test]
    public function test_ticket_presentation_six(): void
    {
        $sede    = Sede::factory()->create();
        $boss    = $this->makeBoss();
        $product = Product::factory()->create(['nombre' => 'Club Rojo', 'activo' => true]);
        $sale    = $this->makeSale($sede, $boss, ['total_sistema' => 6.00]);
        $this->makePresItem($sale, $product, 'Six', 6, 1, 6.00);

        $response = $this->actingAs($boss)->get(route('sales.ticket', $sale));

        $response->assertStatus(200);
        $response->assertSee('Six', false);
    }

    // ── 9. Presentation: Caja ────────────────────────────────────────────────

    #[Test]
    public function test_ticket_presentation_caja(): void
    {
        $sede    = Sede::factory()->create();
        $boss    = $this->makeBoss();
        $product = Product::factory()->create(['nombre' => 'Club Verde', 'activo' => true]);
        $sale    = $this->makeSale($sede, $boss, ['total_sistema' => 56.00]);
        $this->makePresItem($sale, $product, 'Caja', 24, 2, 28.00);

        $response = $this->actingAs($boss)->get(route('sales.ticket', $sale));

        $response->assertStatus(200);
        $response->assertSee('Caja', false);
    }

    // ── 10. 2 × Caja shows "2 × Caja", NOT the 48 base units ─────────────────

    #[Test]
    public function test_ticket_shows_cantidad_presentaciones_not_base_units(): void
    {
        $sede    = Sede::factory()->create();
        $boss    = $this->makeBoss();
        $product = Product::factory()->create(['nombre' => 'Club Verde', 'activo' => true]);
        $sale    = $this->makeSale($sede, $boss, ['total_sistema' => 56.00]);

        // 2 cajas × 24 units/caja = 48 base units deducted from inventory
        $this->makePresItem($sale, $product, 'Caja', 24, 2, 28.00);

        $response = $this->actingAs($boss)->get(route('sales.ticket', $sale));

        $response->assertStatus(200);
        $response->assertSee('2', false);
        $response->assertSee('Caja', false);
        // 48 must NOT appear as the visible customer-facing quantity
        $response->assertDontSee('>48<', false);
    }

    // ── 11. Ticket uses historical precio_unitario ────────────────────────────

    #[Test]
    public function test_ticket_uses_historical_precio_unitario(): void
    {
        $sede    = Sede::factory()->create();
        $boss    = $this->makeBoss();
        $product = Product::factory()->create(['precio_venta' => 5.00, 'activo' => true]);
        $sale    = $this->makeSale($sede, $boss, ['total_sistema' => 5.00]);
        $this->makeLegacyItem($sale, $product, ['precio_unitario' => 5.00, 'subtotal' => 5.00]);

        $response = $this->actingAs($boss)->get(route('sales.ticket', $sale));

        $response->assertStatus(200);
        $response->assertSee('5.00', false);
    }

    // ── 12. Changing Product.precio_venta after sale does not alter ticket ────

    #[Test]
    public function test_ticket_not_affected_by_post_sale_price_change(): void
    {
        $sede    = Sede::factory()->create();
        $boss    = $this->makeBoss();
        $product = Product::factory()->create(['precio_venta' => 5.00, 'activo' => true]);
        $sale    = $this->makeSale($sede, $boss, ['total_sistema' => 5.00]);
        $this->makeLegacyItem($sale, $product, ['precio_unitario' => 5.00, 'subtotal' => 5.00]);

        // Simulate a post-sale price change
        $product->update(['precio_venta' => 99.00]);

        $response = $this->actingAs($boss)->get(route('sales.ticket', $sale));

        $response->assertStatus(200);
        $response->assertSee('5.00', false);   // snapshot price still shown
        $response->assertDontSee('99.00', false);
    }

    // ── 13. Renaming ProductPresentation after sale does not alter ticket ─────

    #[Test]
    public function test_ticket_not_affected_by_presentation_rename(): void
    {
        $sede    = Sede::factory()->create();
        $boss    = $this->makeBoss();
        $product = Product::factory()->create(['activo' => true]);
        $pres    = ProductPresentation::create([
            'product_id'   => $product->id,
            'nombre'       => 'Caja Original',
            'factor_stock' => 12,
            'activo'       => true,
            'sort_order'   => 0,
        ]);
        $sale = $this->makeSale($sede, $boss, ['total_sistema' => 28.00]);
        $this->makePresItem($sale, $product, 'Caja Original', 12, 1, 28.00, [
            'presentation_id' => $pres->id,
        ]);

        // Simulate renaming the presentation after the sale
        $pres->update(['nombre' => 'Caja Nueva']);

        $response = $this->actingAs($boss)->get(route('sales.ticket', $sale));

        $response->assertStatus(200);
        $response->assertSee('Caja Original', false);   // snapshot name
        $response->assertDontSee('Caja Nueva', false);  // live name must NOT appear
    }

    // ── 14. Ticket shows correct descuento ────────────────────────────────────

    #[Test]
    public function test_ticket_shows_correct_descuento(): void
    {
        $sede    = Sede::factory()->create();
        $boss    = $this->makeBoss();
        $product = Product::factory()->create(['activo' => true]);
        $sale    = $this->makeSale($sede, $boss, [
            'total_sistema' => 8.00,
            'descuento'     => 2.00,
        ]);
        $this->makeLegacyItem($sale, $product, ['subtotal' => 10.00, 'precio_unitario' => 10.00]);

        $response = $this->actingAs($boss)->get(route('sales.ticket', $sale));

        $response->assertStatus(200);
        $response->assertSee('2.00', false);
    }

    // ── 15. Ticket shows correct total ───────────────────────────────────────

    #[Test]
    public function test_ticket_shows_correct_total(): void
    {
        $sede    = Sede::factory()->create();
        $boss    = $this->makeBoss();
        $product = Product::factory()->create(['activo' => true]);
        $sale    = $this->makeSale($sede, $boss, ['total_sistema' => 63.50]);
        $this->makeLegacyItem($sale, $product, ['subtotal' => 63.50, 'precio_unitario' => 63.50]);

        $response = $this->actingAs($boss)->get(route('sales.ticket', $sale));

        $response->assertStatus(200);
        $response->assertSee('63.50', false);
    }

    // ── 16. Ticket shows correct sede name ───────────────────────────────────

    #[Test]
    public function test_ticket_shows_correct_sede(): void
    {
        $sede = Sede::factory()->create(['nombre' => 'LA JOYA SPORTBAR']);
        $boss = $this->makeBoss();
        $sale = $this->makeSale($sede, $boss);

        $response = $this->actingAs($boss)->get(route('sales.ticket', $sale));

        $response->assertStatus(200);
        $response->assertSee('LA JOYA SPORTBAR', false);
    }

    // ── 17. Ticket shows correct operator name ────────────────────────────────

    #[Test]
    public function test_ticket_shows_correct_operator_name(): void
    {
        $sede = Sede::factory()->create();
        $boss = $this->makeBoss();
        $boss->forceFill(['name' => 'LuisPERRS'])->save();
        $sale = $this->makeSale($sede, $boss);

        $response = $this->actingAs($boss)->get(route('sales.ticket', $sale));

        $response->assertStatus(200);
        $response->assertSee('LuisPERRS', false);
    }

    // ── 18. Ticket view renders without Blade errors ──────────────────────────

    #[Test]
    public function test_ticket_view_renders_without_blade_errors(): void
    {
        $sede    = Sede::factory()->create();
        $boss    = $this->makeBoss();
        $product = Product::factory()->create(['activo' => true]);
        $sale    = $this->makeSale($sede, $boss);
        $this->makeLegacyItem($sale, $product);

        $response = $this->actingAs($boss)->get(route('sales.ticket', $sale));

        $response->assertStatus(200);
        $response->assertSee('Comprobante de Venta', false);
        $response->assertSee('Gracias por su compra', false);
    }

    // ── 19. History view contains a ticket link ───────────────────────────────

    #[Test]
    public function test_history_contains_ticket_link(): void
    {
        $sede    = Sede::factory()->create();
        $boss    = $this->makeBoss();
        $boss->forceFill(['sede_id' => $sede->id])->save();
        $product = Product::factory()->create(['activo' => true]);
        $sale    = $this->makeSale($sede, $boss);
        $this->makeLegacyItem($sale, $product);

        $response = $this->actingAs($boss)->get(route('sales.history'));

        $response->assertStatus(200);
        $response->assertSee(route('sales.ticket', $sale), false);
    }

    // ── 20. POST /sales returns sale_id in JSON response ─────────────────────

    #[Test]
    public function test_pos_store_sale_returns_sale_id(): void
    {
        $sede    = Sede::factory()->create();
        $product = Product::factory()->create([
            'activo'       => true,
            'precio_venta' => 5.00,
        ]);
        $op = $this->makeOperador($sede);

        Inventory::factory()->create([
            'product_id'    => $product->id,
            'sede_id'       => $sede->id,
            'cantidad_stock' => 10,
        ]);

        $response = $this->actingAs($op)->postJson(route('sales.store'), [
            'items' => [[
                'product_id'              => $product->id,
                'presentation_id'         => null,
                'cantidad_presentaciones' => 1,
            ]],
            'descuento' => 0,
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['success', 'sale_id'])
                 ->assertJson(['success' => true]);

        $this->assertNotNull($response->json('sale_id'));
    }

    // ── 21. GET ticket does not modify inventory or sale ──────────────────────

    #[Test]
    public function test_viewing_ticket_does_not_modify_inventory_or_cash(): void
    {
        $sede    = Sede::factory()->create();
        $boss    = $this->makeBoss();
        $product = Product::factory()->create(['activo' => true]);
        $sale    = $this->makeSale($sede, $boss, ['total_sistema' => 5.00]);
        $this->makeLegacyItem($sale, $product);

        $movementsBefore = InventoryMovement::count();
        $saleTotalBefore = (float) Sale::find($sale->id)->total_sistema;

        $this->actingAs($boss)->get(route('sales.ticket', $sale));

        $this->assertEquals($movementsBefore, InventoryMovement::count());
        $this->assertEquals($saleTotalBefore, (float) Sale::find($sale->id)->total_sistema);
    }

    // ── 22. Venta anulada shows VENTA ANULADA on ticket ──────────────────────

    #[Test]
    public function test_anulada_sale_shows_venta_anulada_on_ticket(): void
    {
        $sede    = Sede::factory()->create();
        $boss    = $this->makeBoss();
        $product = Product::factory()->create(['activo' => true]);
        $sale    = $this->makeSale($sede, $boss, ['estado' => 'anulada']);
        $this->makeLegacyItem($sale, $product);

        $response = $this->actingAs($boss)->get(route('sales.ticket', $sale));

        $response->assertStatus(200);
        $response->assertSee('VENTA ANULADA', false);
    }
}
