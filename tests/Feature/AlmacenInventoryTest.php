<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sede;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AlmacenInventoryTest extends TestCase
{
    use RefreshDatabase;

    private User    $boss;
    private User    $nonBoss;
    private Almacen $almacenA;
    private Almacen $almacenB;
    private Sede    $sedeA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $bossRole = Role::firstOrCreate(['name' => 'boss', 'guard_name' => 'web']);

        $this->boss = User::factory()->create();
        $this->boss->assignRole($bossRole);

        $this->nonBoss = User::factory()->create();

        $this->almacenA = Almacen::factory()->create(['nombre' => 'Almacén A', 'activo' => true]);
        $this->almacenB = Almacen::factory()->create(['nombre' => 'Almacén B', 'activo' => true]);
        $this->sedeA    = Sede::factory()->create(['nombre' => 'Sede A']);
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

    private function makeMovement(Product $product, ?Sede $sede, ?Almacen $almacen, string $tipo = 'entrada', int $cantidad = 10, array $extra = []): InventoryMovement
    {
        return InventoryMovement::create(array_merge([
            'product_id'       => $product->id,
            'sede_id'          => $sede?->id,
            'almacen_id'       => $almacen?->id,
            'tipo'             => $tipo,
            'cantidad'         => $cantidad,
            'motivo'           => 'Test motivo',
            'user_id'          => $this->boss->id,
            'fecha_movimiento' => now(),
        ], $extra));
    }

    // ── 1. Boss accede a show() ────────────────────────────────────────────────

    #[Test]
    public function test_boss_puede_ver_inventario_de_almacen(): void
    {
        $response = $this->actingAs($this->boss)->get(route('almacenes.show', $this->almacenA));
        $response->assertStatus(200);
    }

    // ── 2. Show carga solo inventario del almacén correcto ────────────────────

    #[Test]
    public function test_show_carga_solo_inventario_del_almacen(): void
    {
        $product = Product::factory()->create(['nombre' => 'Producto Exclusivo AlmacénA']);
        $this->makeInventory($product, null, $this->almacenA, 42);

        $response = $this->actingAs($this->boss)->get(route('almacenes.show', $this->almacenA));
        $response->assertSee('Producto Exclusivo AlmacénA');
    }

    // ── 3. Sede con mismo nombre NO se mezcla ─────────────────────────────────

    #[Test]
    public function test_sede_mismo_nombre_no_se_mezcla_en_inventario(): void
    {
        $productAlmacen = Product::factory()->create(['nombre' => 'Producto Solo En Almacén']);
        $productSede    = Product::factory()->create(['nombre' => 'Producto Solo En Sede']);

        $this->makeInventory($productAlmacen, null, $this->almacenA, 105);
        $this->makeInventory($productSede, $this->sedeA, null, 50);

        $response = $this->actingAs($this->boss)->get(route('almacenes.show', $this->almacenA));

        $response->assertSee('Producto Solo En Almacén');
        $response->assertDontSee('Producto Solo En Sede');
    }

    // ── 4. Otro Almacén NO se mezcla ──────────────────────────────────────────

    #[Test]
    public function test_otro_almacen_no_se_mezcla_en_inventario(): void
    {
        $productA = Product::factory()->create(['nombre' => 'Producto AlmacénA Exclusivo']);
        $productB = Product::factory()->create(['nombre' => 'Producto AlmacénB Exclusivo']);

        $this->makeInventory($productA, null, $this->almacenA, 10);
        $this->makeInventory($productB, null, $this->almacenB, 20);

        $response = $this->actingAs($this->boss)->get(route('almacenes.show', $this->almacenA));

        $response->assertSee('Producto AlmacénA Exclusivo');
        $response->assertDontSee('Producto AlmacénB Exclusivo');
    }

    // ── 5. Inventory con stock=0 aparece ──────────────────────────────────────

    #[Test]
    public function test_inventario_con_stock_cero_aparece(): void
    {
        $product = Product::factory()->create(['nombre' => 'Producto Con Cero Stock']);
        $this->makeInventory($product, null, $this->almacenA, 0);

        $response = $this->actingAs($this->boss)->get(route('almacenes.show', $this->almacenA));
        $response->assertSee('Producto Con Cero Stock');
    }

    // ── 6. Almacén inactivo sigue consultable ─────────────────────────────────

    #[Test]
    public function test_almacen_inactivo_sigue_consultable(): void
    {
        $almacenInactivo = Almacen::factory()->create(['nombre' => 'Almacén Inactivo', 'activo' => false]);
        $product = Product::factory()->create(['nombre' => 'Producto En Almacén Inactivo']);
        $this->makeInventory($product, null, $almacenInactivo, 25);

        $response = $this->actingAs($this->boss)->get(route('almacenes.show', $almacenInactivo));

        $response->assertStatus(200);
        $response->assertSee('Producto En Almacén Inactivo');
    }

    // ── 7. Movements carga solo almacen_id correcto ───────────────────────────

    #[Test]
    public function test_movements_carga_solo_movimientos_del_almacen(): void
    {
        $product = Product::factory()->create(['nombre' => 'Producto Movimiento AlmacénA']);
        $this->makeMovement($product, null, $this->almacenA, 'entrada', 10);

        $response = $this->actingAs($this->boss)->get(route('almacenes.movements', $this->almacenA));

        $response->assertStatus(200);
        $response->assertSee('Producto Movimiento AlmacénA');
    }

    // ── 8. Movimiento de transferencia 2.3 aparece ────────────────────────────

    #[Test]
    public function test_movimiento_transferencia_2_3_aparece(): void
    {
        $product = Product::factory()->create(['nombre' => 'Producto Transfer 2.3']);
        $this->makeMovement($product, null, $this->almacenA, 'salida', 20, [
            'reference_type' => 'transfer',
            'reference_id'   => 99,
        ]);

        $response = $this->actingAs($this->boss)->get(route('almacenes.movements', $this->almacenA));

        $response->assertSee('Producto Transfer 2.3');
    }

    // ── 9. Movimiento de Sede NO aparece ──────────────────────────────────────

    #[Test]
    public function test_movimiento_de_sede_no_aparece(): void
    {
        $productSede    = Product::factory()->create(['nombre' => 'Producto Movimiento Sede Exclusivo']);
        $productAlmacen = Product::factory()->create(['nombre' => 'Producto Movimiento Almacén Exclusivo']);

        $this->makeMovement($productSede,    $this->sedeA,  null,          'entrada', 5);
        $this->makeMovement($productAlmacen, null,          $this->almacenA, 'entrada', 5);

        $response = $this->actingAs($this->boss)->get(route('almacenes.movements', $this->almacenA));

        $response->assertSee('Producto Movimiento Almacén Exclusivo');
        $response->assertDontSee('Producto Movimiento Sede Exclusivo');
    }

    // ── 10. Movimiento de otro Almacén NO aparece ─────────────────────────────

    #[Test]
    public function test_movimiento_de_otro_almacen_no_aparece(): void
    {
        $productA = Product::factory()->create(['nombre' => 'Producto Mov AlmacénA Solo']);
        $productB = Product::factory()->create(['nombre' => 'Producto Mov AlmacénB Solo']);

        $this->makeMovement($productA, null, $this->almacenA, 'entrada', 10);
        $this->makeMovement($productB, null, $this->almacenB, 'entrada', 10);

        $response = $this->actingAs($this->boss)->get(route('almacenes.movements', $this->almacenA));

        $response->assertSee('Producto Mov AlmacénA Solo');
        $response->assertDontSee('Producto Mov AlmacénB Solo');
    }

    // ── 11. Usuario sin acceso recibe 403 ─────────────────────────────────────

    #[Test]
    public function test_usuario_sin_acceso_recibe_403_en_show(): void
    {
        $response = $this->actingAs($this->nonBoss)->get(route('almacenes.show', $this->almacenA));
        $response->assertStatus(403);
    }

    #[Test]
    public function test_usuario_sin_acceso_recibe_403_en_movements(): void
    {
        $response = $this->actingAs($this->nonBoss)->get(route('almacenes.movements', $this->almacenA));
        $response->assertStatus(403);
    }

    // ── 12. Ruta /almacenes/nuevo sigue funcionando ───────────────────────────

    #[Test]
    public function test_ruta_almacenes_nuevo_sigue_funcionando(): void
    {
        $response = $this->actingAs($this->boss)->get(route('almacenes.create'));
        $response->assertStatus(200);
    }

    // ── 13. Ruta /almacenes/{id}/editar sigue funcionando ────────────────────

    #[Test]
    public function test_ruta_almacenes_editar_sigue_funcionando(): void
    {
        $response = $this->actingAs($this->boss)->get(route('almacenes.edit', $this->almacenA));
        $response->assertStatus(200);
    }

    // ── 14. Resumen se calcula solo con inventario del almacén ────────────────

    #[Test]
    public function test_resumen_calcula_solo_inventario_del_almacen(): void
    {
        $product = Product::factory()->create();

        // AlmacénA: 105 unidades
        $this->makeInventory($product, null, $this->almacenA, 105);
        // Sede A: 50 unidades del mismo producto (no debe contaminar)
        $this->makeInventory($product, $this->sedeA, null, 50);

        $response = $this->actingAs($this->boss)->get(route('almacenes.show', $this->almacenA));

        $response->assertViewHas('totalStock', 105);
        $response->assertViewHas('totalProductos', 1);
    }
}
