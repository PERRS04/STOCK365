<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sede;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StockTransferControllerTest extends TestCase
{
    use RefreshDatabase;

    private User    $boss;
    private User    $viewer;
    private Sede    $sedeA;
    private Sede    $sedeB;
    private Almacen $almacenA;
    private Almacen $almacenB;
    private Product $productA;
    private Product $productB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        Permission::firstOrCreate(['name' => 'inventory.view',  'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'products.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'transfers.approve', 'guard_name' => 'web']);

        $this->boss = User::factory()->create();
        $this->boss->givePermissionTo(['inventory.view', 'transfers.approve']);

        $this->viewer = User::factory()->create();
        $this->viewer->givePermissionTo('inventory.view');

        $this->sedeA    = Sede::factory()->create(['nombre' => 'Sede A']);
        $this->sedeB    = Sede::factory()->create(['nombre' => 'Sede B']);
        $this->almacenA = Almacen::factory()->create(['nombre' => 'Almacén A', 'activo' => true]);
        $this->almacenB = Almacen::factory()->create(['nombre' => 'Almacén B', 'activo' => true]);
        $this->productA = Product::factory()->create(['activo' => true]);
        $this->productB = Product::factory()->create(['activo' => true]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeInventory(Product $product, ?Sede $sede, ?Almacen $almacen, int $stock): Inventory
    {
        return Inventory::factory()->create([
            'product_id'     => $product->id,
            'sede_id'        => $sede?->id,
            'almacen_id'     => $almacen?->id,
            'cantidad_stock' => $stock,
        ]);
    }

    private function makeTransfer(array $overrides = []): StockTransfer
    {
        return StockTransfer::create(array_merge([
            'from_sede_id'    => $this->sedeA->id,
            'from_almacen_id' => null,
            'to_sede_id'      => $this->sedeB->id,
            'to_almacen_id'   => null,
            'created_by'      => $this->boss->id,
            'estado'          => 'pendiente',
        ], $overrides));
    }

    private function addItem(StockTransfer $transfer, Product $product, int $cantidad): StockTransferItem
    {
        return StockTransferItem::create([
            'transfer_id' => $transfer->id,
            'product_id'  => $product->id,
            'cantidad'    => $cantidad,
        ]);
    }

    private function storeAs(User $user, array $params): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($user)->post(route('transfers.store'), $params);
    }

    private function store(array $params): \Illuminate\Testing\TestResponse
    {
        return $this->storeAs($this->boss, $params);
    }

    private function approve(StockTransfer $transfer, array $params = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->boss)->post(route('transfers.approve', $transfer), $params);
    }

    private function reject(StockTransfer $transfer, array $params = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->boss)->patch(route('transfers.reject', $transfer), $params);
    }

    private function sedeToSedeParams(array $items = []): array
    {
        return [
            'from_sede_id'    => $this->sedeA->id,
            'from_almacen_id' => '',
            'to_sede_id'      => $this->sedeB->id,
            'to_almacen_id'   => '',
            'items'           => $items ?: [['product_id' => $this->productA->id, 'cantidad' => 5]],
        ];
    }

    // ── 1. index() ────────────────────────────────────────────────────────────

    #[Test]
    public function test_index_returns_200_with_permission(): void
    {
        $response = $this->actingAs($this->boss)->get(route('transfers.index'));
        $response->assertStatus(200);
    }

    #[Test]
    public function test_index_403_sin_permiso(): void
    {
        $noPerms = User::factory()->create();
        $response = $this->actingAs($noPerms)->get(route('transfers.index'));
        $response->assertStatus(403);
    }

    #[Test]
    public function test_index_eager_loads_almacenes(): void
    {
        $t = $this->makeTransfer([
            'from_sede_id'    => null,
            'from_almacen_id' => $this->almacenA->id,
            'to_sede_id'      => null,
            'to_almacen_id'   => $this->almacenB->id,
        ]);
        $response = $this->actingAs($this->boss)->get(route('transfers.index'));
        $response->assertStatus(200);
        $response->assertSee($this->almacenA->nombre);
        $response->assertSee($this->almacenB->nombre);
    }

    // ── 2. create() ───────────────────────────────────────────────────────────

    #[Test]
    public function test_create_returns_view_con_sedes_almacenes_products(): void
    {
        $response = $this->actingAs($this->boss)->get(route('transfers.create'));
        $response->assertStatus(200);
        $response->assertViewHas('sedes');
        $response->assertViewHas('almacenes');
        $response->assertViewHas('products');
    }

    #[Test]
    public function test_create_403_sin_permiso(): void
    {
        $noPerms = User::factory()->create();
        $response = $this->actingAs($noPerms)->get(route('transfers.create'));
        $response->assertStatus(403);
    }

    // ── 3. store() — combinaciones exitosas ───────────────────────────────────

    #[Test]
    public function test_store_sede_a_sede_crea_transfer(): void
    {
        $this->makeInventory($this->productA, $this->sedeA, null, 50);

        $response = $this->store($this->sedeToSedeParams());

        $response->assertRedirect(route('transfers.index'));
        $this->assertDatabaseHas('stock_transfers', [
            'from_sede_id'    => $this->sedeA->id,
            'from_almacen_id' => null,
            'to_sede_id'      => $this->sedeB->id,
            'to_almacen_id'   => null,
            'estado'          => 'pendiente',
        ]);
        $this->assertDatabaseHas('stock_transfer_items', [
            'product_id' => $this->productA->id,
            'cantidad'   => 5,
        ]);
    }

    #[Test]
    public function test_store_sede_a_almacen_crea_transfer(): void
    {
        $this->makeInventory($this->productA, $this->sedeA, null, 50);

        $response = $this->store([
            'from_sede_id'    => $this->sedeA->id,
            'from_almacen_id' => '',
            'to_sede_id'      => '',
            'to_almacen_id'   => $this->almacenA->id,
            'items'           => [['product_id' => $this->productA->id, 'cantidad' => 10]],
        ]);

        $response->assertRedirect(route('transfers.index'));
        $this->assertDatabaseHas('stock_transfers', [
            'from_sede_id'    => $this->sedeA->id,
            'from_almacen_id' => null,
            'to_sede_id'      => null,
            'to_almacen_id'   => $this->almacenA->id,
        ]);
    }

    #[Test]
    public function test_store_almacen_a_sede_crea_transfer(): void
    {
        $this->makeInventory($this->productA, null, $this->almacenA, 50);

        $response = $this->store([
            'from_sede_id'    => '',
            'from_almacen_id' => $this->almacenA->id,
            'to_sede_id'      => $this->sedeA->id,
            'to_almacen_id'   => '',
            'items'           => [['product_id' => $this->productA->id, 'cantidad' => 10]],
        ]);

        $response->assertRedirect(route('transfers.index'));
        $this->assertDatabaseHas('stock_transfers', [
            'from_sede_id'    => null,
            'from_almacen_id' => $this->almacenA->id,
            'to_sede_id'      => $this->sedeA->id,
            'to_almacen_id'   => null,
        ]);
    }

    #[Test]
    public function test_store_almacen_a_almacen_crea_transfer(): void
    {
        $this->makeInventory($this->productA, null, $this->almacenA, 50);

        $response = $this->store([
            'from_sede_id'    => '',
            'from_almacen_id' => $this->almacenA->id,
            'to_sede_id'      => '',
            'to_almacen_id'   => $this->almacenB->id,
            'items'           => [['product_id' => $this->productA->id, 'cantidad' => 10]],
        ]);

        $response->assertRedirect(route('transfers.index'));
        $this->assertDatabaseHas('stock_transfers', [
            'from_sede_id'    => null,
            'from_almacen_id' => $this->almacenA->id,
            'to_sede_id'      => null,
            'to_almacen_id'   => $this->almacenB->id,
        ]);
    }

    // ── 4. store() — validaciones XOR ─────────────────────────────────────────

    #[Test]
    public function test_store_xor_from_ambos_seteados_error(): void
    {
        $response = $this->store([
            'from_sede_id'    => $this->sedeA->id,
            'from_almacen_id' => $this->almacenA->id,
            'to_sede_id'      => $this->sedeB->id,
            'to_almacen_id'   => '',
            'items'           => [['product_id' => $this->productA->id, 'cantidad' => 5]],
        ]);
        $response->assertSessionHasErrors('from');
    }

    #[Test]
    public function test_store_xor_from_ninguno_seteado_error(): void
    {
        $response = $this->store([
            'from_sede_id'    => '',
            'from_almacen_id' => '',
            'to_sede_id'      => $this->sedeB->id,
            'to_almacen_id'   => '',
            'items'           => [['product_id' => $this->productA->id, 'cantidad' => 5]],
        ]);
        $response->assertSessionHasErrors('from');
    }

    #[Test]
    public function test_store_xor_to_ambos_seteados_error(): void
    {
        $response = $this->store([
            'from_sede_id'    => $this->sedeA->id,
            'from_almacen_id' => '',
            'to_sede_id'      => $this->sedeB->id,
            'to_almacen_id'   => $this->almacenA->id,
            'items'           => [['product_id' => $this->productA->id, 'cantidad' => 5]],
        ]);
        $response->assertSessionHasErrors('to');
    }

    #[Test]
    public function test_store_xor_to_ninguno_seteado_error(): void
    {
        $response = $this->store([
            'from_sede_id'    => $this->sedeA->id,
            'from_almacen_id' => '',
            'to_sede_id'      => '',
            'to_almacen_id'   => '',
            'items'           => [['product_id' => $this->productA->id, 'cantidad' => 5]],
        ]);
        $response->assertSessionHasErrors('to');
    }

    #[Test]
    public function test_store_misma_sede_origen_destino_error(): void
    {
        $this->makeInventory($this->productA, $this->sedeA, null, 50);

        $response = $this->store([
            'from_sede_id'    => $this->sedeA->id,
            'from_almacen_id' => '',
            'to_sede_id'      => $this->sedeA->id,
            'to_almacen_id'   => '',
            'items'           => [['product_id' => $this->productA->id, 'cantidad' => 5]],
        ]);
        $response->assertSessionHasErrors('to_sede_id');
    }

    #[Test]
    public function test_store_mismo_almacen_origen_destino_error(): void
    {
        $this->makeInventory($this->productA, null, $this->almacenA, 50);

        $response = $this->store([
            'from_sede_id'    => '',
            'from_almacen_id' => $this->almacenA->id,
            'to_sede_id'      => '',
            'to_almacen_id'   => $this->almacenA->id,
            'items'           => [['product_id' => $this->productA->id, 'cantidad' => 5]],
        ]);
        $response->assertSessionHasErrors('to_almacen_id');
    }

    // ── 5. store() — validaciones de items ────────────────────────────────────

    #[Test]
    public function test_store_producto_duplicado_error(): void
    {
        $this->makeInventory($this->productA, $this->sedeA, null, 50);

        $response = $this->store([
            'from_sede_id'    => $this->sedeA->id,
            'from_almacen_id' => '',
            'to_sede_id'      => $this->sedeB->id,
            'to_almacen_id'   => '',
            'items'           => [
                ['product_id' => $this->productA->id, 'cantidad' => 5],
                ['product_id' => $this->productA->id, 'cantidad' => 3],
            ],
        ]);
        $response->assertSessionHas('error');
        $this->assertStringContainsString('mismo producto', session('error'));
    }

    #[Test]
    public function test_store_items_vacios_error(): void
    {
        $response = $this->store([
            'from_sede_id' => $this->sedeA->id,
            'to_sede_id'   => $this->sedeB->id,
            'items'        => [],
        ]);
        $response->assertSessionHasErrors('items');
    }

    #[Test]
    public function test_store_cantidad_cero_error(): void
    {
        $response = $this->store([
            'from_sede_id'    => $this->sedeA->id,
            'from_almacen_id' => '',
            'to_sede_id'      => $this->sedeB->id,
            'to_almacen_id'   => '',
            'items'           => [['product_id' => $this->productA->id, 'cantidad' => 0]],
        ]);
        $response->assertSessionHasErrors('items.0.cantidad');
    }

    // ── 6. store() — pre-check stock ──────────────────────────────────────────

    #[Test]
    public function test_store_stock_insuficiente_en_sede_error(): void
    {
        $this->makeInventory($this->productA, $this->sedeA, null, 2);

        $response = $this->store($this->sedeToSedeParams([
            ['product_id' => $this->productA->id, 'cantidad' => 10],
        ]));

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Stock insuficiente', session('error'));
    }

    #[Test]
    public function test_store_stock_insuficiente_en_almacen_error(): void
    {
        $this->makeInventory($this->productA, null, $this->almacenA, 3);

        $response = $this->store([
            'from_sede_id'    => '',
            'from_almacen_id' => $this->almacenA->id,
            'to_sede_id'      => $this->sedeA->id,
            'to_almacen_id'   => '',
            'items'           => [['product_id' => $this->productA->id, 'cantidad' => 10]],
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Stock insuficiente', session('error'));
    }

    #[Test]
    public function test_store_sin_inventario_en_origen_error(): void
    {
        // No inventory record at sedeA for productA
        $response = $this->store($this->sedeToSedeParams([
            ['product_id' => $this->productA->id, 'cantidad' => 1],
        ]));

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Stock insuficiente', session('error'));
    }

    #[Test]
    public function test_store_403_sin_permiso(): void
    {
        $noPerms = User::factory()->create();
        $response = $this->storeAs($noPerms, $this->sedeToSedeParams());
        $response->assertStatus(403);
    }

    // ── 7. approve() — 4 combinaciones de stock ───────────────────────────────

    #[Test]
    public function test_approve_sede_a_sede_stock_correcto(): void
    {
        $this->makeInventory($this->productA, $this->sedeA, null, 50);
        $this->makeInventory($this->productA, $this->sedeB, null, 20);

        $transfer = $this->makeTransfer();
        $this->addItem($transfer, $this->productA, 15);

        $this->approve($transfer)->assertRedirect(route('transfers.index'));

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->productA->id,
            'sede_id'        => $this->sedeA->id,
            'almacen_id'     => null,
            'cantidad_stock' => 35,
        ]);
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->productA->id,
            'sede_id'        => $this->sedeB->id,
            'almacen_id'     => null,
            'cantidad_stock' => 35,
        ]);
    }

    #[Test]
    public function test_approve_sede_a_almacen_stock_correcto(): void
    {
        $this->makeInventory($this->productA, $this->sedeA, null, 50);

        $transfer = $this->makeTransfer([
            'from_sede_id'    => $this->sedeA->id,
            'from_almacen_id' => null,
            'to_sede_id'      => null,
            'to_almacen_id'   => $this->almacenA->id,
        ]);
        $this->addItem($transfer, $this->productA, 10);

        $this->approve($transfer)->assertRedirect(route('transfers.index'));

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->productA->id,
            'sede_id'        => $this->sedeA->id,
            'almacen_id'     => null,
            'cantidad_stock' => 40,
        ]);
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->productA->id,
            'sede_id'        => null,
            'almacen_id'     => $this->almacenA->id,
            'cantidad_stock' => 10,
        ]);
    }

    #[Test]
    public function test_approve_almacen_a_sede_stock_correcto(): void
    {
        $this->makeInventory($this->productA, null, $this->almacenA, 100);

        $transfer = $this->makeTransfer([
            'from_sede_id'    => null,
            'from_almacen_id' => $this->almacenA->id,
            'to_sede_id'      => $this->sedeA->id,
            'to_almacen_id'   => null,
        ]);
        $this->addItem($transfer, $this->productA, 25);

        $this->approve($transfer)->assertRedirect(route('transfers.index'));

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->productA->id,
            'sede_id'        => null,
            'almacen_id'     => $this->almacenA->id,
            'cantidad_stock' => 75,
        ]);
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->productA->id,
            'sede_id'        => $this->sedeA->id,
            'almacen_id'     => null,
            'cantidad_stock' => 25,
        ]);
    }

    #[Test]
    public function test_approve_almacen_a_almacen_stock_correcto(): void
    {
        $this->makeInventory($this->productA, null, $this->almacenA, 125);

        $transfer = $this->makeTransfer([
            'from_sede_id'    => null,
            'from_almacen_id' => $this->almacenA->id,
            'to_sede_id'      => null,
            'to_almacen_id'   => $this->almacenB->id,
        ]);
        $this->addItem($transfer, $this->productA, 20);

        $this->approve($transfer)->assertRedirect(route('transfers.index'));

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->productA->id,
            'sede_id'        => null,
            'almacen_id'     => $this->almacenA->id,
            'cantidad_stock' => 105,
        ]);
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->productA->id,
            'sede_id'        => null,
            'almacen_id'     => $this->almacenB->id,
            'cantidad_stock' => 20,
        ]);
    }

    // ── 8. Prueba crítica de independencia de ubicaciones ─────────────────────

    #[Test]
    public function test_approve_almacen_no_afecta_sede_con_mismo_producto(): void
    {
        // AlmacenA tiene 125 unidades de productA
        $this->makeInventory($this->productA, null, $this->almacenA, 125);
        // SedeA también tiene 50 unidades del mismo producto
        $this->makeInventory($this->productA, $this->sedeA, null, 50);

        // Transferimos 20 de AlmacenA → AlmacenB
        $transfer = $this->makeTransfer([
            'from_sede_id'    => null,
            'from_almacen_id' => $this->almacenA->id,
            'to_sede_id'      => null,
            'to_almacen_id'   => $this->almacenB->id,
        ]);
        $this->addItem($transfer, $this->productA, 20);

        $this->approve($transfer);

        // AlmacenA: 125 - 20 = 105
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->productA->id,
            'sede_id'        => null,
            'almacen_id'     => $this->almacenA->id,
            'cantidad_stock' => 105,
        ]);
        // AlmacenB: 0 + 20 = 20
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->productA->id,
            'sede_id'        => null,
            'almacen_id'     => $this->almacenB->id,
            'cantidad_stock' => 20,
        ]);
        // SedeA: INTACTA, sigue en 50
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->productA->id,
            'sede_id'        => $this->sedeA->id,
            'almacen_id'     => null,
            'cantidad_stock' => 50,
        ]);
    }

    // ── 9. approve() — movimientos generados ──────────────────────────────────

    #[Test]
    public function test_approve_genera_dos_movimientos_por_item(): void
    {
        $this->makeInventory($this->productA, $this->sedeA, null, 50);
        $transfer = $this->makeTransfer();
        $this->addItem($transfer, $this->productA, 10);

        $this->approve($transfer);

        $this->assertEquals(2, InventoryMovement::where('reference_id', $transfer->id)
            ->where('reference_type', 'transfer')
            ->count());
    }

    #[Test]
    public function test_approve_movimiento_salida_metadata_correcto(): void
    {
        $this->makeInventory($this->productA, null, $this->almacenA, 50);

        $transfer = $this->makeTransfer([
            'from_sede_id'    => null,
            'from_almacen_id' => $this->almacenA->id,
            'to_sede_id'      => $this->sedeB->id,
            'to_almacen_id'   => null,
        ]);
        $this->addItem($transfer, $this->productA, 10);

        $this->approve($transfer);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id'     => $this->productA->id,
            'sede_id'        => null,
            'almacen_id'     => $this->almacenA->id,
            'tipo'           => 'salida',
            'cantidad'       => 10,
            'reference_id'   => $transfer->id,
            'reference_type' => 'transfer',
        ]);
    }

    #[Test]
    public function test_approve_movimiento_entrada_metadata_correcto(): void
    {
        $this->makeInventory($this->productA, $this->sedeA, null, 50);

        $transfer = $this->makeTransfer([
            'from_sede_id'    => $this->sedeA->id,
            'from_almacen_id' => null,
            'to_sede_id'      => null,
            'to_almacen_id'   => $this->almacenB->id,
        ]);
        $this->addItem($transfer, $this->productA, 10);

        $this->approve($transfer);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id'     => $this->productA->id,
            'sede_id'        => null,
            'almacen_id'     => $this->almacenB->id,
            'tipo'           => 'entrada',
            'cantidad'       => 10,
            'reference_id'   => $transfer->id,
            'reference_type' => 'transfer',
        ]);
    }

    #[Test]
    public function test_approve_movimientos_tienen_user_id(): void
    {
        $this->makeInventory($this->productA, $this->sedeA, null, 50);
        $transfer = $this->makeTransfer();
        $this->addItem($transfer, $this->productA, 5);

        $this->approve($transfer);

        $movements = InventoryMovement::where('reference_id', $transfer->id)
            ->where('reference_type', 'transfer')
            ->get();

        foreach ($movements as $m) {
            $this->assertEquals($this->boss->id, $m->user_id);
        }
    }

    // ── 10. isTransfer() — compatibilidad forward/backward ────────────────────

    #[Test]
    public function test_isTransfer_backward_tipo_transferencia(): void
    {
        $movement = new \App\Models\InventoryMovement([
            'tipo'           => 'transferencia',
            'reference_type' => null,
        ]);
        $this->assertTrue($movement->isTransfer());
    }

    #[Test]
    public function test_isTransfer_forward_salida_con_reference_type_transfer(): void
    {
        $movement = new \App\Models\InventoryMovement([
            'tipo'           => 'salida',
            'reference_type' => 'transfer',
        ]);
        $this->assertTrue($movement->isTransfer());
    }

    #[Test]
    public function test_isTransfer_forward_entrada_con_reference_type_transfer(): void
    {
        $movement = new \App\Models\InventoryMovement([
            'tipo'           => 'entrada',
            'reference_type' => 'transfer',
        ]);
        $this->assertTrue($movement->isTransfer());
    }

    #[Test]
    public function test_isTransfer_false_para_salida_sin_reference_type(): void
    {
        $movement = new \App\Models\InventoryMovement([
            'tipo'           => 'salida',
            'reference_type' => null,
        ]);
        $this->assertFalse($movement->isTransfer());
    }

    // ── 11. approve() — estado cambia a aprobado ──────────────────────────────

    #[Test]
    public function test_approve_transfer_estado_aprobado(): void
    {
        $this->makeInventory($this->productA, $this->sedeA, null, 50);
        $transfer = $this->makeTransfer();
        $this->addItem($transfer, $this->productA, 5);

        $this->approve($transfer, ['notas_aprobacion' => 'OK verificado']);

        $this->assertDatabaseHas('stock_transfers', [
            'id'               => $transfer->id,
            'estado'           => 'aprobado',
            'approved_by'      => $this->boss->id,
            'notas_aprobacion' => 'OK verificado',
        ]);
        $this->assertNotNull($transfer->fresh()->aprobado_at);
    }

    // ── 12. approve() — multi-producto ordenado ───────────────────────────────

    #[Test]
    public function test_approve_multi_producto_todas_las_mutaciones_ocurren(): void
    {
        $this->makeInventory($this->productA, $this->sedeA, null, 50);
        $this->makeInventory($this->productB, $this->sedeA, null, 30);

        $transfer = $this->makeTransfer();
        $this->addItem($transfer, $this->productA, 10);
        $this->addItem($transfer, $this->productB, 5);

        $this->approve($transfer);

        $this->assertDatabaseHas('inventories', [
            'product_id' => $this->productA->id, 'sede_id' => $this->sedeA->id, 'cantidad_stock' => 40,
        ]);
        $this->assertDatabaseHas('inventories', [
            'product_id' => $this->productB->id, 'sede_id' => $this->sedeA->id, 'cantidad_stock' => 25,
        ]);
        $this->assertEquals(4, InventoryMovement::where('reference_id', $transfer->id)
            ->where('reference_type', 'transfer')->count());
    }

    // ── 13. approve() — crea inventario destino si no existe ─────────────────

    #[Test]
    public function test_approve_crea_inventario_destino_si_no_existe(): void
    {
        $this->makeInventory($this->productA, $this->sedeA, null, 50);

        // sedeB has no inventory record for productA
        $this->assertDatabaseMissing('inventories', [
            'product_id' => $this->productA->id,
            'sede_id'    => $this->sedeB->id,
        ]);

        $transfer = $this->makeTransfer();
        $this->addItem($transfer, $this->productA, 15);

        $this->approve($transfer);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->productA->id,
            'sede_id'        => $this->sedeB->id,
            'cantidad_stock' => 15,
        ]);
    }

    // ── 14. approve() — doble aprobación bloqueada ────────────────────────────

    #[Test]
    public function test_approve_ya_aprobado_error_sin_mutacion_duplicada(): void
    {
        $this->makeInventory($this->productA, $this->sedeA, null, 50);
        $transfer = $this->makeTransfer(['estado' => 'aprobado']);
        $this->addItem($transfer, $this->productA, 5);

        $response = $this->approve($transfer);

        $response->assertSessionHas('error');
        // No movements created (transfer was already aprobado, no stock change)
        $this->assertEquals(0, InventoryMovement::where('reference_id', $transfer->id)
            ->where('reference_type', 'transfer')->count());
        // Sede A stock unchanged
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->productA->id,
            'sede_id'        => $this->sedeA->id,
            'cantidad_stock' => 50,
        ]);
    }

    #[Test]
    public function test_approve_ya_rechazado_error(): void
    {
        $this->makeInventory($this->productA, $this->sedeA, null, 50);
        $transfer = $this->makeTransfer(['estado' => 'rechazado']);
        $this->addItem($transfer, $this->productA, 5);

        $response = $this->approve($transfer);
        $response->assertSessionHas('error');
    }

    // ── 15. approve() — fallos de stock ───────────────────────────────────────

    #[Test]
    public function test_approve_stock_insuficiente_origen_error(): void
    {
        $this->makeInventory($this->productA, $this->sedeA, null, 5);

        $transfer = $this->makeTransfer();
        $this->addItem($transfer, $this->productA, 100);

        $response = $this->approve($transfer);

        $response->assertSessionHas('error');
        // Transfer still pending, no stock change
        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer->id, 'estado' => 'pendiente']);
        $this->assertDatabaseHas('inventories', [
            'product_id' => $this->productA->id, 'sede_id' => $this->sedeA->id, 'cantidad_stock' => 5,
        ]);
    }

    #[Test]
    public function test_approve_sin_inventario_en_origen_error(): void
    {
        // No inventory record at sedeA for productA
        $transfer = $this->makeTransfer();
        $this->addItem($transfer, $this->productA, 10);

        $response = $this->approve($transfer);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer->id, 'estado' => 'pendiente']);
    }

    // ── 16. approve() — rollback atómico en fallo parcial ────────────────────

    #[Test]
    public function test_approve_rollback_si_segundo_producto_falla(): void
    {
        // productA tiene stock, productB no
        $this->makeInventory($this->productA, $this->sedeA, null, 50);
        // productB has NO inventory at sedeA

        $transfer = $this->makeTransfer();
        // Ensure productA has lower ID than productB so it runs first
        $pA = Product::factory()->create(['activo' => true]);
        $pB = Product::factory()->create(['activo' => true]);
        // Ensure pA.id < pB.id (factory sequential)
        $this->makeInventory($pA, $this->sedeA, null, 50);
        // pB has no inventory → will throw InventoryNotFoundException

        $transfer2 = $this->makeTransfer();
        $this->addItem($transfer2, $pA, 10);
        $this->addItem($transfer2, $pB, 5);

        $response = $this->approve($transfer2);

        $response->assertSessionHas('error');

        // pA stock must be rolled back to 50
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $pA->id,
            'sede_id'        => $this->sedeA->id,
            'cantidad_stock' => 50,
        ]);
        // 0 movements committed
        $this->assertEquals(0, InventoryMovement::where('reference_id', $transfer2->id)
            ->where('reference_type', 'transfer')->count());
        // Transfer still pendiente
        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer2->id, 'estado' => 'pendiente']);
    }

    // ── 17. approve() — permisos ──────────────────────────────────────────────

    #[Test]
    public function test_approve_403_sin_permiso_transfers_approve(): void
    {
        $transfer = $this->makeTransfer();
        $response = $this->actingAs($this->viewer)->post(route('transfers.approve', $transfer));
        $response->assertStatus(403);
    }

    #[Test]
    public function test_approve_403_con_products_create_pero_sin_transfers_approve(): void
    {
        $userWithProductsCreate = User::factory()->create();
        $userWithProductsCreate->givePermissionTo(['inventory.view', 'products.create']);
        $transfer = $this->makeTransfer();
        $response = $this->actingAs($userWithProductsCreate)->post(route('transfers.approve', $transfer));
        $response->assertStatus(403);
    }

    // ── 18. reject() ──────────────────────────────────────────────────────────

    #[Test]
    public function test_reject_pendiente_a_rechazado(): void
    {
        $transfer = $this->makeTransfer();

        $response = $this->reject($transfer, ['notas_aprobacion' => 'Motivo de rechazo']);

        $response->assertRedirect(route('transfers.index'));
        $this->assertDatabaseHas('stock_transfers', [
            'id'               => $transfer->id,
            'estado'           => 'rechazado',
            'approved_by'      => $this->boss->id,
            'notas_aprobacion' => 'Motivo de rechazo',
        ]);
    }

    #[Test]
    public function test_reject_nota_requerida(): void
    {
        $transfer = $this->makeTransfer();

        $response = $this->reject($transfer, []);
        $response->assertSessionHasErrors('notas_aprobacion');
    }

    #[Test]
    public function test_reject_ya_procesado_error(): void
    {
        $transfer = $this->makeTransfer(['estado' => 'aprobado']);

        $response = $this->reject($transfer, ['notas_aprobacion' => 'Motivo']);
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer->id, 'estado' => 'aprobado']);
    }

    #[Test]
    public function test_reject_ya_rechazado_error(): void
    {
        $transfer = $this->makeTransfer(['estado' => 'rechazado']);

        $response = $this->reject($transfer, ['notas_aprobacion' => 'Motivo']);
        $response->assertSessionHas('error');
    }

    #[Test]
    public function test_reject_no_toca_el_stock(): void
    {
        $this->makeInventory($this->productA, $this->sedeA, null, 50);

        $transfer = $this->makeTransfer();
        $this->addItem($transfer, $this->productA, 20);

        $this->reject($transfer, ['notas_aprobacion' => 'No procede']);

        // Stock unchanged
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $this->productA->id,
            'sede_id'        => $this->sedeA->id,
            'cantidad_stock' => 50,
        ]);
        $this->assertEquals(0, InventoryMovement::where('reference_id', $transfer->id)->count());
    }

    #[Test]
    public function test_reject_403_sin_permiso_transfers_approve(): void
    {
        $transfer = $this->makeTransfer();
        $response = $this->actingAs($this->viewer)->patch(
            route('transfers.reject', $transfer),
            ['notas_aprobacion' => 'Motivo']
        );
        $response->assertStatus(403);
    }

    // ── 19. show() ────────────────────────────────────────────────────────────

    #[Test]
    public function test_show_almacen_transfer_no_crashea(): void
    {
        $transfer = $this->makeTransfer([
            'from_sede_id'    => null,
            'from_almacen_id' => $this->almacenA->id,
            'to_sede_id'      => null,
            'to_almacen_id'   => $this->almacenB->id,
        ]);
        $this->addItem($transfer, $this->productA, 5);

        $response = $this->actingAs($this->boss)->get(route('transfers.show', $transfer));

        $response->assertStatus(200);
        $response->assertSee($this->almacenA->nombre);
        $response->assertSee($this->almacenB->nombre);
    }

    #[Test]
    public function test_creacion_guarda_datos_utiles_en_auditoria(): void
    {
        $this->makeInventory($this->productA, $this->sedeA, null, 20);

        $this->store($this->sedeToSedeParams([
            ['product_id' => $this->productA->id, 'cantidad' => 5],
        ]));

        $transfer = StockTransfer::latest('id')->firstOrFail();

        $log = \App\Models\ActivityLog::where('action', 'transferencia.creada')
            ->latest('id')
            ->firstOrFail();

        $label = $this->productA->nombre . " (#{$this->productA->id})";

        $this->assertSame($transfer->id, $log->model_id);
        $this->assertSame('pendiente', $log->new_values['estado'] ?? null);
        $this->assertSame($this->sedeA->id, $log->new_values['origen_sede_id'] ?? null);
        $this->assertSame($this->sedeB->id, $log->new_values['destino_sede_id'] ?? null);
        $this->assertSame(5, $log->new_values[$label] ?? null);
    }

    #[Test]
    public function test_aprobacion_guarda_cambio_de_estado_en_auditoria(): void
    {
        $this->makeInventory($this->productA, $this->sedeA, null, 20);

        $transfer = $this->makeTransfer();
        $this->addItem($transfer, $this->productA, 5);

        $this->approve($transfer);

        $log = \App\Models\ActivityLog::where('action', 'transferencia.aprobada')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($transfer->id, $log->model_id);
        $this->assertSame('pendiente', $log->old_values['estado'] ?? null);
        $this->assertSame('aprobado', $log->new_values['estado'] ?? null);
    }

    #[Test]
    public function test_rechazo_guarda_estado_y_motivo_en_auditoria(): void
    {
        $transfer = $this->makeTransfer();

        $this->reject($transfer, [
            'notas_aprobacion' => 'Stock reservado para otra sede',
        ]);

        $log = \App\Models\ActivityLog::where('action', 'transferencia.rechazada')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($transfer->id, $log->model_id);
        $this->assertSame('pendiente', $log->old_values['estado'] ?? null);
        $this->assertSame('rechazado', $log->new_values['estado'] ?? null);
        $this->assertSame(
            'Stock reservado para otra sede',
            $log->new_values['motivo_rechazo'] ?? null
        );
    }

    // ── C1: view guard — transfers.approve vs products.create ────────────────

    #[Test]
    public function test_supervisor_con_transfers_approve_ve_botones_aprobar_rechazar(): void
    {
        Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'transfers.approve', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'inventory.view', 'guard_name' => 'web']);

        $supervisor = User::factory()->create(['role' => 'supervisor']);
        $supervisor->assignRole('supervisor');
        $supervisor->givePermissionTo(['transfers.approve', 'inventory.view']);

        $transfer = $this->makeTransfer(['estado' => 'pendiente', 'created_by' => $this->boss->id]);

        $response = $this->actingAs($supervisor)->get(route('transfers.show', $transfer));
        $response->assertStatus(200);
        $response->assertSee('Aprobar Transferencia');
    }

    #[Test]
    public function test_usuario_con_products_create_pero_sin_transfers_approve_no_ve_botones(): void
    {
        Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'products.create', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'inventory.view', 'guard_name' => 'web']);

        $user = User::factory()->create(['role' => 'supervisor']);
        $user->assignRole('supervisor');
        $user->givePermissionTo(['products.create', 'inventory.view']);
        // NOT transfers.approve

        $transfer = $this->makeTransfer(['estado' => 'pendiente', 'created_by' => $this->boss->id]);

        $response = $this->actingAs($user)->get(route('transfers.show', $transfer));
        $response->assertStatus(200);
        $response->assertDontSee('Aprobar Transferencia');
    }

    #[Test]
    public function test_boss_ve_botones_aprobar_rechazar(): void
    {
        $transfer = $this->makeTransfer(['estado' => 'pendiente', 'created_by' => $this->boss->id]);

        $response = $this->actingAs($this->boss)->get(route('transfers.show', $transfer));
        $response->assertStatus(200);
        $response->assertSee('Aprobar Transferencia');
    }
}
