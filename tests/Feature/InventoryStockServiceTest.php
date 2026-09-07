<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidLocationException;
use App\Exceptions\InventoryNotFoundException;
use App\Models\Almacen;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sede;
use App\Models\User;
use App\Services\InventoryStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryStockServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryStockService $service;
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InventoryStockService();
        $this->userId  = User::factory()->create()->id;
    }

    private function makeProduct(): Product
    {
        return Product::factory()->create();
    }

    private function makeSede(): Sede
    {
        return Sede::factory()->create();
    }

    private function makeAlmacen(): Almacen
    {
        return Almacen::factory()->create();
    }

    private function makeInventory(Product $product, ?Sede $sede, ?Almacen $almacen, int $stock): Inventory
    {
        return Inventory::factory()->create([
            'product_id'     => $product->id,
            'sede_id'        => $sede?->id,
            'almacen_id'     => $almacen?->id,
            'cantidad_stock' => $stock,
        ]);
    }

    // ── Tests 1-3: Sede operations ────────────────────────────────────────────

    #[Test]
    public function test_entrada_incrementa_stock_en_sede(): void
    {
        $product = $this->makeProduct();
        $sede    = $this->makeSede();

        $this->service->entrada($product->id, 5, $sede->id, null, null, $this->userId);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $sede->id,
            'almacen_id'     => null,
            'cantidad_stock' => 5,
        ]);
    }

    #[Test]
    public function test_salida_decrementa_stock_en_sede(): void
    {
        $product = $this->makeProduct();
        $sede    = $this->makeSede();
        $this->makeInventory($product, $sede, null, 10);

        $this->service->salida($product->id, 3, $sede->id, null, null, $this->userId);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $sede->id,
            'cantidad_stock' => 7,
        ]);
    }

    #[Test]
    public function test_perdida_decrementa_stock_en_sede(): void
    {
        $product = $this->makeProduct();
        $sede    = $this->makeSede();
        $this->makeInventory($product, $sede, null, 10);

        $this->service->perdida($product->id, 4, $sede->id, null, null, $this->userId);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $sede->id,
            'cantidad_stock' => 6,
        ]);
    }

    // ── Tests 4-6: Almacén operations ─────────────────────────────────────────

    #[Test]
    public function test_entrada_incrementa_stock_en_almacen(): void
    {
        $product = $this->makeProduct();
        $almacen = $this->makeAlmacen();

        $this->service->entrada($product->id, 8, null, $almacen->id, null, $this->userId);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => null,
            'almacen_id'     => $almacen->id,
            'cantidad_stock' => 8,
        ]);
    }

    #[Test]
    public function test_salida_decrementa_stock_en_almacen(): void
    {
        $product = $this->makeProduct();
        $almacen = $this->makeAlmacen();
        $this->makeInventory($product, null, $almacen, 20);

        $this->service->salida($product->id, 7, null, $almacen->id, null, $this->userId);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'almacen_id'     => $almacen->id,
            'cantidad_stock' => 13,
        ]);
    }

    #[Test]
    public function test_perdida_decrementa_stock_en_almacen(): void
    {
        $product = $this->makeProduct();
        $almacen = $this->makeAlmacen();
        $this->makeInventory($product, null, $almacen, 15);

        $this->service->perdida($product->id, 5, null, $almacen->id, null, $this->userId);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'almacen_id'     => $almacen->id,
            'cantidad_stock' => 10,
        ]);
    }

    // ── Tests 7-12: Stock protection ──────────────────────────────────────────

    #[Test]
    public function test_salida_sin_stock_lanza_insufficient_stock(): void
    {
        $product = $this->makeProduct();
        $sede    = $this->makeSede();
        $this->makeInventory($product, $sede, null, 0);

        $this->expectException(InsufficientStockException::class);

        $this->service->salida($product->id, 1, $sede->id, null, null, $this->userId);
    }

    #[Test]
    public function test_salida_con_cantidad_exacta_es_permitida(): void
    {
        $product = $this->makeProduct();
        $sede    = $this->makeSede();
        $this->makeInventory($product, $sede, null, 5);

        $this->service->salida($product->id, 5, $sede->id, null, null, $this->userId);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $sede->id,
            'cantidad_stock' => 0,
        ]);
    }

    #[Test]
    public function test_salida_expone_disponible_y_requerido_en_exception(): void
    {
        $product = $this->makeProduct();
        $sede    = $this->makeSede();
        $this->makeInventory($product, $sede, null, 3);

        try {
            $this->service->salida($product->id, 5, $sede->id, null, null, $this->userId);
            $this->fail('Expected InsufficientStockException');
        } catch (InsufficientStockException $e) {
            $this->assertEquals(3, $e->disponible);
            $this->assertEquals(5, $e->requerido);
        }
    }

    #[Test]
    public function test_perdida_sin_stock_lanza_insufficient_stock(): void
    {
        $product = $this->makeProduct();
        $sede    = $this->makeSede();
        $this->makeInventory($product, $sede, null, 0);

        $this->expectException(InsufficientStockException::class);

        $this->service->perdida($product->id, 1, $sede->id, null, null, $this->userId);
    }

    #[Test]
    public function test_perdida_con_cantidad_exacta_es_permitida(): void
    {
        $product = $this->makeProduct();
        $sede    = $this->makeSede();
        $this->makeInventory($product, $sede, null, 5);

        $this->service->perdida($product->id, 5, $sede->id, null, null, $this->userId);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $sede->id,
            'cantidad_stock' => 0,
        ]);
    }

    #[Test]
    public function test_set_stock_absolute_a_cero_es_permitido(): void
    {
        $product = $this->makeProduct();
        $sede    = $this->makeSede();
        $this->makeInventory($product, $sede, null, 10);

        $this->service->setStockAbsolute($product->id, 0, $sede->id, null, null, $this->userId);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $sede->id,
            'cantidad_stock' => 0,
        ]);
    }

    // ── Tests 13-14: Sequential stock accumulation ────────────────────────────

    #[Test]
    public function test_entradas_secuenciales_acumulan_stock_correctamente(): void
    {
        $product = $this->makeProduct();
        $sede    = $this->makeSede();

        $this->service->entrada($product->id, 10, $sede->id, null, null, $this->userId);
        $this->service->entrada($product->id, 5, $sede->id, null, null, $this->userId);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $sede->id,
            'cantidad_stock' => 15,
        ]);
    }

    #[Test]
    public function test_salidas_secuenciales_reducen_stock_correctamente(): void
    {
        $product = $this->makeProduct();
        $sede    = $this->makeSede();
        $this->makeInventory($product, $sede, null, 20);

        $this->service->salida($product->id, 7, $sede->id, null, null, $this->userId);
        $this->service->salida($product->id, 6, $sede->id, null, null, $this->userId);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $sede->id,
            'cantidad_stock' => 7,
        ]);
    }

    // ── Tests 15-16: Atomicity / rollback ─────────────────────────────────────

    #[Test]
    public function test_salida_fallida_no_crea_movimiento_ni_modifica_stock(): void
    {
        $product = $this->makeProduct();
        $sede    = $this->makeSede();
        $this->makeInventory($product, $sede, null, 2);

        $countBefore = InventoryMovement::count();

        try {
            $this->service->salida($product->id, 10, $sede->id, null, null, $this->userId);
        } catch (InsufficientStockException) {}

        $this->assertEquals($countBefore, InventoryMovement::count());
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $sede->id,
            'cantidad_stock' => 2,
        ]);
    }

    #[Test]
    public function test_exception_externa_en_transaccion_hace_rollback_completo(): void
    {
        $product     = $this->makeProduct();
        $sede        = $this->makeSede();
        $countBefore = InventoryMovement::count();

        try {
            DB::transaction(function () use ($product, $sede) {
                $this->service->entrada($product->id, 10, $sede->id, null, null, $this->userId);
                throw new \RuntimeException('Simulated external failure');
            });
        } catch (\RuntimeException) {}

        $this->assertEquals($countBefore, InventoryMovement::count());
        $this->assertDatabaseMissing('inventories', [
            'product_id' => $product->id,
            'sede_id'    => $sede->id,
        ]);
    }

    // ── Tests 17-19: Audit trail ──────────────────────────────────────────────

    #[Test]
    public function test_entrada_crea_inventory_movement_con_tipo_entrada(): void
    {
        $product = $this->makeProduct();
        $sede    = $this->makeSede();

        $this->service->entrada($product->id, 5, $sede->id, null, null, $this->userId);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'sede_id'    => $sede->id,
            'almacen_id' => null,
            'tipo'       => 'entrada',
            'cantidad'   => 5,
            'user_id'    => $this->userId,
        ]);
    }

    #[Test]
    public function test_salida_crea_inventory_movement_con_tipo_salida(): void
    {
        $product = $this->makeProduct();
        $sede    = $this->makeSede();
        $this->makeInventory($product, $sede, null, 10);

        $this->service->salida($product->id, 3, $sede->id, null, null, $this->userId);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'sede_id'    => $sede->id,
            'tipo'       => 'salida',
            'cantidad'   => 3,
            'user_id'    => $this->userId,
        ]);
    }

    #[Test]
    public function test_perdida_crea_inventory_movement_con_tipo_perdida(): void
    {
        $product = $this->makeProduct();
        $sede    = $this->makeSede();
        $this->makeInventory($product, $sede, null, 10);

        $this->service->perdida($product->id, 4, $sede->id, null, null, $this->userId);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'sede_id'    => $sede->id,
            'tipo'       => 'pérdida',
            'cantidad'   => 4,
            'user_id'    => $this->userId,
        ]);
    }

    // ── Tests 20-21: Location validation ──────────────────────────────────────

    #[Test]
    public function test_ambos_sede_y_almacen_lanza_invalid_location(): void
    {
        $product = $this->makeProduct();
        $sede    = $this->makeSede();
        $almacen = $this->makeAlmacen();

        $this->expectException(InvalidLocationException::class);

        $this->service->entrada($product->id, 5, $sede->id, $almacen->id, null, $this->userId);
    }

    #[Test]
    public function test_ninguna_location_lanza_invalid_location(): void
    {
        $product = $this->makeProduct();

        $this->expectException(InvalidLocationException::class);

        $this->service->entrada($product->id, 5, null, null, null, $this->userId);
    }

    // ── Tests 22-23: Sauces independence (sede ↔ almacén isolation) ──────────

    #[Test]
    public function test_entrada_en_almacen_no_afecta_stock_en_sede(): void
    {
        $product = $this->makeProduct();
        $sede    = $this->makeSede();
        $almacen = $this->makeAlmacen();
        $this->makeInventory($product, $sede, null, 10);
        $this->makeInventory($product, null, $almacen, 5);

        $this->service->entrada($product->id, 20, null, $almacen->id, null, $this->userId);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $sede->id,
            'almacen_id'     => null,
            'cantidad_stock' => 10,
        ]);
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => null,
            'almacen_id'     => $almacen->id,
            'cantidad_stock' => 25,
        ]);
    }

    #[Test]
    public function test_salida_de_sede_no_afecta_stock_en_almacen(): void
    {
        $product = $this->makeProduct();
        $sede    = $this->makeSede();
        $almacen = $this->makeAlmacen();
        $this->makeInventory($product, $sede, null, 10);
        $this->makeInventory($product, null, $almacen, 5);

        $this->service->salida($product->id, 3, $sede->id, null, null, $this->userId);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $sede->id,
            'almacen_id'     => null,
            'cantidad_stock' => 7,
        ]);
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => null,
            'almacen_id'     => $almacen->id,
            'cantidad_stock' => 5,
        ]);
    }

    // ── Tests 24-26: setStockAbsolute ─────────────────────────────────────────

    #[Test]
    public function test_set_stock_absolute_ajuste_hacia_arriba(): void
    {
        $product = $this->makeProduct();
        $sede    = $this->makeSede();
        $this->makeInventory($product, $sede, null, 5);

        $this->service->setStockAbsolute($product->id, 10, $sede->id, null, null, $this->userId);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $sede->id,
            'cantidad_stock' => 10,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'sede_id'    => $sede->id,
            'tipo'       => 'ajuste',
            'cantidad'   => 5,
        ]);
    }

    #[Test]
    public function test_set_stock_absolute_ajuste_hacia_abajo(): void
    {
        $product = $this->makeProduct();
        $sede    = $this->makeSede();
        $this->makeInventory($product, $sede, null, 10);

        $this->service->setStockAbsolute($product->id, 3, $sede->id, null, null, $this->userId);

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $sede->id,
            'cantidad_stock' => 3,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'sede_id'    => $sede->id,
            'tipo'       => 'ajuste',
            'cantidad'   => 7,
        ]);
    }

    #[Test]
    public function test_set_stock_absolute_sin_cambio_no_crea_movimiento(): void
    {
        $product     = $this->makeProduct();
        $sede        = $this->makeSede();
        $this->makeInventory($product, $sede, null, 5);
        $countBefore = InventoryMovement::count();

        $this->service->setStockAbsolute($product->id, 5, $sede->id, null, null, $this->userId);

        $this->assertEquals($countBefore, InventoryMovement::count());
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $sede->id,
            'cantidad_stock' => 5,
        ]);
    }

    // ── Test 27: Multi-product deadlock prevention ────────────────────────────

    #[Test]
    public function test_entradas_multiples_productos_en_orden_asc_no_fallan(): void
    {
        $p1   = $this->makeProduct();
        $p2   = $this->makeProduct();
        $p3   = $this->makeProduct();
        $sede = $this->makeSede();

        // Caller sorts ASC by product_id before iterating — deadlock-safe ordering
        $items = [
            ['product_id' => $p3->id, 'cantidad' => 5],
            ['product_id' => $p1->id, 'cantidad' => 3],
            ['product_id' => $p2->id, 'cantidad' => 7],
        ];
        usort($items, fn($a, $b) => $a['product_id'] <=> $b['product_id']);

        DB::transaction(function () use ($items, $sede) {
            foreach ($items as $item) {
                $this->service->entrada($item['product_id'], $item['cantidad'], $sede->id, null, null, $this->userId);
            }
        });

        $this->assertDatabaseHas('inventories', ['product_id' => $p1->id, 'sede_id' => $sede->id, 'cantidad_stock' => 3]);
        $this->assertDatabaseHas('inventories', ['product_id' => $p2->id, 'sede_id' => $sede->id, 'cantidad_stock' => 7]);
        $this->assertDatabaseHas('inventories', ['product_id' => $p3->id, 'sede_id' => $sede->id, 'cantidad_stock' => 5]);
    }

    // ── Tests 28-29: Transfer atomicity ───────────────────────────────────────

    #[Test]
    public function test_transfer_salida_mas_entrada_ambas_confirmadas(): void
    {
        $product = $this->makeProduct();
        $sede1   = $this->makeSede();
        $sede2   = $this->makeSede();
        $this->makeInventory($product, $sede1, null, 10);
        $this->makeInventory($product, $sede2, null, 0);

        DB::transaction(function () use ($product, $sede1, $sede2) {
            $this->service->salida($product->id, 5, $sede1->id, null, null, $this->userId);
            $this->service->entrada($product->id, 5, $sede2->id, null, null, $this->userId);
        });

        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $sede1->id,
            'cantidad_stock' => 5,
        ]);
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $sede2->id,
            'cantidad_stock' => 5,
        ]);
    }

    #[Test]
    public function test_transfer_rollback_si_segunda_operacion_falla(): void
    {
        $product = $this->makeProduct();
        $sede1   = $this->makeSede();
        $sede2   = $this->makeSede();
        $this->makeInventory($product, $sede1, null, 10);
        $this->makeInventory($product, $sede2, null, 0);

        try {
            DB::transaction(function () use ($product, $sede1, $sede2) {
                $this->service->salida($product->id, 5, $sede1->id, null, null, $this->userId);
                // Fails: sede2 has 0 stock — rolls back the entire outer transaction
                $this->service->salida($product->id, 1, $sede2->id, null, null, $this->userId);
            });
        } catch (InsufficientStockException) {}

        // sede1 stock must be fully rolled back to 10
        $this->assertDatabaseHas('inventories', [
            'product_id'     => $product->id,
            'sede_id'        => $sede1->id,
            'cantidad_stock' => 10,
        ]);
    }
}
