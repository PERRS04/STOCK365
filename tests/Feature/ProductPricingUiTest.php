<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\PresentationSedePrice;
use App\Models\SaleItem;
use App\Models\Sale;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductPricingUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        Permission::firstOrCreate(['name' => 'sales.create', 'guard_name' => 'web']);
        $bossRole = Role::firstOrCreate(['name' => 'boss', 'guard_name' => 'web']);
        $bossRole->syncPermissions(Permission::all());
    }

    private function makeBoss(): User
    {
        $boss = User::factory()->create(['role' => 'boss', 'sede_id' => null]);
        $boss->assignRole('boss');
        return $boss;
    }

    private function makeOperador(Sede $sede): User
    {
        $op = User::factory()->create(['role' => 'operador', 'sede_id' => $sede->id]);
        $op->givePermissionTo('sales.create');
        return $op;
    }

    // ── 1. Boss can access the pricing view ───────────────────────────────────

    #[Test]
    public function test_boss_can_access_product_pricing_view(): void
    {
        $product = Product::factory()->create(['nombre' => 'Agua Mineral', 'activo' => true]);
        $boss    = $this->makeBoss();

        $response = $this->actingAs($boss)->get(route('product-pricing.index', $product));

        $response->assertStatus(200);
        $response->assertSee('Agua Mineral');
        $response->assertSee('Presentaciones');
    }

    // ── 2. Operator (non-boss) is denied the pricing view ─────────────────────

    #[Test]
    public function test_operator_cannot_access_product_pricing_view(): void
    {
        $sede    = Sede::factory()->create();
        $product = Product::factory()->create(['activo' => true]);
        $op      = $this->makeOperador($sede);

        $response = $this->actingAs($op)->get(route('product-pricing.index', $product));

        $response->assertStatus(403);
    }

    // ── 3. Unauthenticated user is redirected to login ────────────────────────

    #[Test]
    public function test_unauthenticated_user_cannot_access_product_pricing_view(): void
    {
        $product = Product::factory()->create(['activo' => true]);

        $response = $this->get(route('product-pricing.index', $product));

        $response->assertRedirect(route('login'));
    }

    // ── 4. Boss can create a new presentation ─────────────────────────────────

    #[Test]
    public function test_boss_can_create_presentation(): void
    {
        $product = Product::factory()->create(['activo' => true]);
        $boss    = $this->makeBoss();

        $response = $this->actingAs($boss)->post(
            route('product-presentations.store', $product),
            ['nombre' => 'Caja x 12', 'factor_stock' => 12, 'sort_order' => 0]
        );

        $response->assertRedirect(route('product-pricing.index', $product));
        $this->assertDatabaseHas('product_presentations', [
            'product_id'   => $product->id,
            'nombre'       => 'Caja x 12',
            'factor_stock' => 12,
            'activo'       => true,
        ]);
    }

    // ── 5. Boss can add a global presentation price (sede_id = null) ──────────

    #[Test]
    public function test_boss_can_add_global_presentation_price(): void
    {
        $product = Product::factory()->create(['activo' => true]);
        $boss    = $this->makeBoss();
        $pres    = ProductPresentation::create([
            'product_id'   => $product->id,
            'nombre'       => 'Bolsa x 6',
            'factor_stock' => 6,
            'activo'       => true,
            'sort_order'   => 0,
        ]);

        $response = $this->actingAs($boss)->post(
            route('presentation-prices.store', [$product, $pres]),
            ['sede_id' => '', 'precio_venta' => 25.50]
        );

        $response->assertRedirect(route('product-pricing.index', $product));
        $this->assertDatabaseHas('presentation_sede_prices', [
            'presentation_id' => $pres->id,
            'sede_id'         => null,
            'precio_venta'    => 25.50,
        ]);
    }

    // ── 6. factor_stock update blocked when sales history exists ──────────────

    #[Test]
    public function test_factor_stock_change_blocked_when_sales_exist(): void
    {
        $sede    = Sede::factory()->create();
        $product = Product::factory()->create(['activo' => true]);
        $boss    = $this->makeBoss();
        $pres    = ProductPresentation::create([
            'product_id'   => $product->id,
            'nombre'       => 'Pack x 3',
            'factor_stock' => 3,
            'activo'       => true,
            'sort_order'   => 0,
        ]);

        // Simulate an existing sale item referencing this presentation
        $sale = Sale::create([
            'sede_id'       => $sede->id,
            'user_id'       => $boss->id,
            'total_sistema' => 10.00,
            'descuento'     => 0,
            'estado'        => 'completada',
            'fecha_venta'   => now(),
        ]);
        SaleItem::create([
            'sale_id'                 => $sale->id,
            'product_id'              => $product->id,
            'presentation_id'         => $pres->id,
            'presentation_name'       => 'Pack x 3',
            'presentation_factor'     => 3,
            'cantidad_presentaciones' => 1,
            'cantidad'                => 3,
            'precio_unitario'         => 10.00,
            'costo_unitario'          => 5.00,
            'subtotal'                => 10.00,
        ]);

        $response = $this->actingAs($boss)->patch(
            route('product-presentations.update', [$product, $pres]),
            ['nombre' => 'Pack x 3', 'factor_stock' => 6]  // factor changed 3→6
        );

        $response->assertSessionHasErrors('factor_stock');
        $this->assertDatabaseHas('product_presentations', [
            'id'           => $pres->id,
            'factor_stock' => 3,  // unchanged
        ]);
    }

    // ── 7. Presentation with sales is deactivated (not hard-deleted) ──────────

    #[Test]
    public function test_presentation_with_sales_is_deactivated_not_deleted(): void
    {
        $sede    = Sede::factory()->create();
        $product = Product::factory()->create(['activo' => true]);
        $boss    = $this->makeBoss();
        $pres    = ProductPresentation::create([
            'product_id'   => $product->id,
            'nombre'       => 'Docena',
            'factor_stock' => 12,
            'activo'       => true,
            'sort_order'   => 0,
        ]);

        $sale = Sale::create([
            'sede_id'       => $sede->id,
            'user_id'       => $boss->id,
            'total_sistema' => 15.00,
            'descuento'     => 0,
            'estado'        => 'completada',
            'fecha_venta'   => now(),
        ]);
        SaleItem::create([
            'sale_id'                 => $sale->id,
            'product_id'              => $product->id,
            'presentation_id'         => $pres->id,
            'presentation_name'       => 'Docena',
            'presentation_factor'     => 12,
            'cantidad_presentaciones' => 1,
            'cantidad'                => 12,
            'precio_unitario'         => 15.00,
            'costo_unitario'          => 8.00,
            'subtotal'                => 15.00,
        ]);

        $response = $this->actingAs($boss)->delete(
            route('product-presentations.destroy', [$product, $pres])
        );

        $response->assertRedirect(route('product-pricing.index', $product));
        // Record still exists but is inactive
        $this->assertDatabaseHas('product_presentations', [
            'id'     => $pres->id,
            'activo' => false,
        ]);
    }
}
