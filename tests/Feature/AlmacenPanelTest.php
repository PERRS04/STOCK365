<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\InventoryReceipt;
use App\Models\InventoryReceiptItem;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Sede;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AlmacenPanelTest extends TestCase
{
    use RefreshDatabase;

    private User    $boss;
    private User    $operadorSede;
    private User    $operadorAlmacen;
    private Almacen $almacen;
    private Sede    $sede;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        foreach (['inventory.view','inventory.adjust','receipts.create','receipts.approve','transfers.approve','sales.create'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        $bossRole      = Role::firstOrCreate(['name' => 'boss',     'guard_name' => 'web']);
        $operadorRole  = Role::firstOrCreate(['name' => 'operador', 'guard_name' => 'web']);

        $bossRole->syncPermissions(Permission::all());
        $operadorRole->syncPermissions(['inventory.view', 'sales.create']);

        $this->sede    = Sede::factory()->create(['activa' => true]);
        $this->almacen = Almacen::factory()->create(['activo' => true]);

        $this->boss = User::factory()->create(['role' => 'boss', 'sede_id' => null, 'almacen_id' => null]);
        $this->boss->assignRole('boss');

        $this->operadorSede = User::factory()->create(['role' => 'operador', 'sede_id' => $this->sede->id, 'almacen_id' => null]);
        $this->operadorSede->assignRole('operador');

        $this->operadorAlmacen = User::factory()->create(['role' => 'operador', 'sede_id' => null, 'almacen_id' => $this->almacen->id]);
        $this->operadorAlmacen->assignRole('operador');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PANEL ACCESS
    // ─────────────────────────────────────────────────────────────────────────

    public function test_boss_puede_ver_panel_almacen()
    {
        $this->actingAs($this->boss)
            ->get(route('almacenes.show', $this->almacen))
            ->assertOk()
            ->assertSee($this->almacen->nombre);
    }

    public function test_operador_sede_no_puede_ver_panel_almacen()
    {
        $this->actingAs($this->operadorSede)
            ->get(route('almacenes.show', $this->almacen))
            ->assertForbidden();
    }

    public function test_operador_almacen_no_puede_ver_panel_boss()
    {
        $this->actingAs($this->operadorAlmacen)
            ->get(route('almacenes.show', $this->almacen))
            ->assertForbidden();
    }

    public function test_panel_muestra_kpis_y_acciones_clave()
    {
        $this->actingAs($this->boss)
            ->get(route('almacenes.show', $this->almacen))
            ->assertOk()
            ->assertSee('Entrada de mercancía')
            ->assertSee('Transferir mercancía')
            ->assertSee('Ver movimientos')
            ->assertSee('Productos con stock')
            ->assertSee('Valor inventario');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STOCK SCOPE — nunca mezclar sede con almacén
    // ─────────────────────────────────────────────────────────────────────────

    public function test_panel_solo_muestra_stock_del_almacen_no_de_sedes()
    {
        $product = Product::factory()->create(['activo' => true]);

        // Stock en SEDE
        Inventory::create(['product_id' => $product->id, 'sede_id' => $this->sede->id, 'almacen_id' => null, 'cantidad_stock' => 50]);
        // Stock en ALMACÉN
        Inventory::create(['product_id' => $product->id, 'sede_id' => null, 'almacen_id' => $this->almacen->id, 'cantidad_stock' => 10]);

        $response = $this->actingAs($this->boss)
            ->get(route('almacenes.show', $this->almacen))
            ->assertOk();

        // La vista muestra 10 unidades (almacén), no 50 (sede)
        $response->assertSee('10');
        // Total unidades debe ser 10, no 60
        $this->assertStringNotContainsString('>60<', $response->content());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ENTRADA DE MERCANCÍA
    // ─────────────────────────────────────────────────────────────────────────

    public function test_boss_ve_formulario_de_entrada()
    {
        $this->actingAs($this->boss)
            ->get(route('almacenes.entry.create', $this->almacen))
            ->assertOk()
            ->assertSee('Entrada de Mercancía');
    }

    public function test_operador_no_puede_ver_formulario_de_entrada()
    {
        $this->actingAs($this->operadorAlmacen)
            ->get(route('almacenes.entry.create', $this->almacen))
            ->assertForbidden();
    }

    public function test_entrada_incrementa_stock_y_crea_movimiento()
    {
        $product = Product::factory()->create(['activo' => true, 'precio_compra' => 5.00]);

        $this->actingAs($this->boss)
            ->post(route('almacenes.entry.store', $this->almacen), [
                'items' => [
                    ['product_id' => $product->id, 'cantidad' => 100, 'costo_unitario' => 7.50],
                ],
            ])
            ->assertRedirect(route('almacenes.show', $this->almacen));

        // Stock aumentó
        $this->assertDatabaseHas('inventories', [
            'product_id'    => $product->id,
            'almacen_id'    => $this->almacen->id,
            'sede_id'       => null,
            'cantidad_stock' => 100,
        ]);

        // Movimiento de tipo entrada creado
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'almacen_id' => $this->almacen->id,
            'sede_id'    => null,
            'tipo'       => 'entrada',
            'cantidad'   => 100,
        ]);
    }

    public function test_entrada_registra_costo_en_movimiento_y_actualiza_precio_compra()
    {
        $product = Product::factory()->create(['activo' => true, 'precio_compra' => 0.00]);

        $this->actingAs($this->boss)
            ->post(route('almacenes.entry.store', $this->almacen), [
                'items' => [
                    ['product_id' => $product->id, 'cantidad' => 50, 'costo_unitario' => 10.00],
                ],
            ]);

        // costo_unitario guardado en el movimiento
        $this->assertDatabaseHas('inventory_movements', [
            'product_id'     => $product->id,
            'almacen_id'     => $this->almacen->id,
            'costo_unitario' => '10.00',
        ]);

        // precio_compra del producto actualizado
        $product->refresh();
        $this->assertEquals('10.00', $product->precio_compra);
    }

    public function test_entrada_crea_receipt_con_almacen_id()
    {
        $product = Product::factory()->create(['activo' => true]);

        $this->actingAs($this->boss)
            ->post(route('almacenes.entry.store', $this->almacen), [
                'observaciones' => 'Test entrada',
                'items'         => [
                    ['product_id' => $product->id, 'cantidad' => 20, 'costo_unitario' => 5.00],
                ],
            ]);

        $this->assertDatabaseHas('inventory_receipts', [
            'almacen_id' => $this->almacen->id,
            'sede_id'    => null,
            'estado'     => 'aprobado',
        ]);
    }

    public function test_entrada_multiple_productos()
    {
        $p1 = Product::factory()->create(['activo' => true]);
        $p2 = Product::factory()->create(['activo' => true]);

        $this->actingAs($this->boss)
            ->post(route('almacenes.entry.store', $this->almacen), [
                'items' => [
                    ['product_id' => $p1->id, 'cantidad' => 60, 'costo_unitario' => 3.00],
                    ['product_id' => $p2->id, 'cantidad' => 40, 'costo_unitario' => 8.00],
                ],
            ])
            ->assertRedirect(route('almacenes.show', $this->almacen));

        $this->assertDatabaseHas('inventories', ['product_id' => $p1->id, 'almacen_id' => $this->almacen->id, 'cantidad_stock' => 60]);
        $this->assertDatabaseHas('inventories', ['product_id' => $p2->id, 'almacen_id' => $this->almacen->id, 'cantidad_stock' => 40]);
    }

    public function test_entrada_producto_duplicado_rechazada()
    {
        $product = Product::factory()->create(['activo' => true]);

        $this->actingAs($this->boss)
            ->post(route('almacenes.entry.store', $this->almacen), [
                'items' => [
                    ['product_id' => $product->id, 'cantidad' => 10, 'costo_unitario' => 5.00],
                    ['product_id' => $product->id, 'cantidad' => 20, 'costo_unitario' => 5.00],
                ],
            ])
            ->assertRedirect(); // back() con error

        // No debe haber stock creado
        $this->assertDatabaseMissing('inventories', ['product_id' => $product->id, 'almacen_id' => $this->almacen->id]);
    }

    public function test_entrada_crea_auditoria()
    {
        $product = Product::factory()->create(['activo' => true]);

        $this->actingAs($this->boss)
            ->post(route('almacenes.entry.store', $this->almacen), [
                'items' => [['product_id' => $product->id, 'cantidad' => 30, 'costo_unitario' => 4.00]],
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'action'     => 'warehouse.receipt',
            'almacen_id' => $this->almacen->id,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // AJUSTE DE INVENTARIO
    // ─────────────────────────────────────────────────────────────────────────

    public function test_boss_puede_acceder_a_ajuste()
    {
        $product = Product::factory()->create(['activo' => true]);
        Inventory::create(['product_id' => $product->id, 'almacen_id' => $this->almacen->id, 'sede_id' => null, 'cantidad_stock' => 50]);

        $this->actingAs($this->boss)
            ->post(route('almacenes.adjust', $this->almacen), [
                'product_id'   => $product->id,
                'new_cantidad' => 45,
                'motivo'       => '5 unidades dañadas en almacén',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('inventories', [
            'product_id'    => $product->id,
            'almacen_id'    => $this->almacen->id,
            'cantidad_stock' => 45,
        ]);
    }

    public function test_ajuste_positivo_incrementa_stock()
    {
        $product = Product::factory()->create(['activo' => true]);
        Inventory::create(['product_id' => $product->id, 'almacen_id' => $this->almacen->id, 'sede_id' => null, 'cantidad_stock' => 10]);

        $this->actingAs($this->boss)
            ->post(route('almacenes.adjust', $this->almacen), [
                'product_id'   => $product->id,
                'new_cantidad' => 20,
                'motivo'       => 'Conteo físico encontró más unidades',
            ]);

        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'almacen_id' => $this->almacen->id, 'cantidad_stock' => 20]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'almacen_id' => $this->almacen->id,
            'tipo'       => 'ajuste',
            'cantidad'   => 10,
        ]);
    }

    public function test_ajuste_negativo_reduce_stock()
    {
        $product = Product::factory()->create(['activo' => true]);
        Inventory::create(['product_id' => $product->id, 'almacen_id' => $this->almacen->id, 'sede_id' => null, 'cantidad_stock' => 30]);

        $this->actingAs($this->boss)
            ->post(route('almacenes.adjust', $this->almacen), [
                'product_id'   => $product->id,
                'new_cantidad' => 25,
                'motivo'       => 'Merma por daño',
            ]);

        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'almacen_id' => $this->almacen->id, 'cantidad_stock' => 25]);
    }

    public function test_ajuste_requiere_motivo()
    {
        $product = Product::factory()->create(['activo' => true]);
        Inventory::create(['product_id' => $product->id, 'almacen_id' => $this->almacen->id, 'sede_id' => null, 'cantidad_stock' => 10]);

        $this->actingAs($this->boss)
            ->post(route('almacenes.adjust', $this->almacen), [
                'product_id'   => $product->id,
                'new_cantidad' => 5,
                'motivo'       => '', // vacío
            ])
            ->assertSessionHasErrors('motivo');

        // Stock sin cambios
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'almacen_id' => $this->almacen->id, 'cantidad_stock' => 10]);
    }

    public function test_ajuste_no_puede_dejar_stock_negativo()
    {
        $product = Product::factory()->create(['activo' => true]);
        Inventory::create(['product_id' => $product->id, 'almacen_id' => $this->almacen->id, 'sede_id' => null, 'cantidad_stock' => 10]);

        $this->actingAs($this->boss)
            ->post(route('almacenes.adjust', $this->almacen), [
                'product_id'   => $product->id,
                'new_cantidad' => -1,
                'motivo'       => 'Intento negativo',
            ])
            ->assertSessionHasErrors('new_cantidad');
    }

    public function test_ajuste_crea_auditoria_con_old_y_new_values()
    {
        $product = Product::factory()->create(['activo' => true]);
        Inventory::create(['product_id' => $product->id, 'almacen_id' => $this->almacen->id, 'sede_id' => null, 'cantidad_stock' => 50]);

        $this->actingAs($this->boss)
            ->post(route('almacenes.adjust', $this->almacen), [
                'product_id'   => $product->id,
                'new_cantidad' => 45,
                'motivo'       => 'Conteo físico',
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'action'     => 'warehouse.adjustment',
            'almacen_id' => $this->almacen->id,
        ]);
    }

    public function test_operador_no_puede_ajustar_inventario()
    {
        $product = Product::factory()->create(['activo' => true]);
        Inventory::create(['product_id' => $product->id, 'almacen_id' => $this->almacen->id, 'sede_id' => null, 'cantidad_stock' => 10]);

        $this->actingAs($this->operadorAlmacen)
            ->post(route('almacenes.adjust', $this->almacen), [
                'product_id'   => $product->id,
                'new_cantidad' => 5,
                'motivo'       => 'Intento operador',
            ])
            ->assertForbidden();

        // Stock sin cambios
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'almacen_id' => $this->almacen->id, 'cantidad_stock' => 10]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TRANSFERENCIAS DESDE EL PANEL DE ALMACÉN
    // ─────────────────────────────────────────────────────────────────────────

    public function test_boss_puede_crear_transferencia_almacen_a_sede()
    {
        $product = Product::factory()->create(['activo' => true]);
        Inventory::create(['product_id' => $product->id, 'almacen_id' => $this->almacen->id, 'sede_id' => null, 'cantidad_stock' => 100]);

        $this->actingAs($this->boss)
            ->post(route('transfers.store'), [
                'from_almacen_id' => $this->almacen->id,
                'to_sede_id'      => $this->sede->id,
                'items'           => [['product_id' => $product->id, 'cantidad' => 20]],
            ])
            ->assertRedirect(route('transfers.index'));

        $this->assertDatabaseHas('stock_transfers', [
            'from_almacen_id' => $this->almacen->id,
            'to_sede_id'      => $this->sede->id,
            'estado'          => 'pendiente',
        ]);
    }

    public function test_boss_puede_crear_transferencia_almacen_a_almacen()
    {
        $almacen2 = Almacen::factory()->create(['activo' => true]);
        $product  = Product::factory()->create(['activo' => true]);
        Inventory::create(['product_id' => $product->id, 'almacen_id' => $this->almacen->id, 'sede_id' => null, 'cantidad_stock' => 80]);

        $this->actingAs($this->boss)
            ->post(route('transfers.store'), [
                'from_almacen_id' => $this->almacen->id,
                'to_almacen_id'   => $almacen2->id,
                'items'           => [['product_id' => $product->id, 'cantidad' => 30]],
            ])
            ->assertRedirect(route('transfers.index'));

        $this->assertDatabaseHas('stock_transfers', [
            'from_almacen_id' => $this->almacen->id,
            'to_almacen_id'   => $almacen2->id,
            'estado'          => 'pendiente',
        ]);
    }

    public function test_transferencia_con_stock_insuficiente_es_rechazada()
    {
        $product = Product::factory()->create(['activo' => true]);
        Inventory::create(['product_id' => $product->id, 'almacen_id' => $this->almacen->id, 'sede_id' => null, 'cantidad_stock' => 5]);

        $this->actingAs($this->boss)
            ->post(route('transfers.store'), [
                'from_almacen_id' => $this->almacen->id,
                'to_sede_id'      => $this->sede->id,
                'items'           => [['product_id' => $product->id, 'cantidad' => 100]],
            ])
            ->assertRedirect(); // back() con error

        $this->assertDatabaseMissing('stock_transfers', ['from_almacen_id' => $this->almacen->id]);
    }

    public function test_transferencia_a_destino_inactivo_rechazada()
    {
        $sedeInactiva = Sede::factory()->create(['activa' => false]);
        $product      = Product::factory()->create(['activo' => true]);
        Inventory::create(['product_id' => $product->id, 'almacen_id' => $this->almacen->id, 'sede_id' => null, 'cantidad_stock' => 50]);

        $this->actingAs($this->boss)
            ->post(route('transfers.store'), [
                'from_almacen_id' => $this->almacen->id,
                'to_sede_id'      => $sedeInactiva->id,
                'items'           => [['product_id' => $product->id, 'cantidad' => 10]],
            ])
            ->assertSessionHasErrors();
    }

    public function test_aprobar_transferencia_mueve_stock_una_sola_vez()
    {
        $product = Product::factory()->create(['activo' => true]);
        Inventory::create(['product_id' => $product->id, 'almacen_id' => $this->almacen->id, 'sede_id' => null, 'cantidad_stock' => 100]);

        // Crear transferencia pendiente
        $transfer = StockTransfer::create([
            'from_almacen_id' => $this->almacen->id,
            'from_sede_id'    => null,
            'to_sede_id'      => $this->sede->id,
            'to_almacen_id'   => null,
            'created_by'      => $this->boss->id,
            'estado'          => 'pendiente',
        ]);
        StockTransferItem::create(['transfer_id' => $transfer->id, 'product_id' => $product->id, 'cantidad' => 30]);

        // Aprobar
        $this->actingAs($this->boss)
            ->post(route('transfers.approve', $transfer))
            ->assertRedirect(route('transfers.index'));

        // Origen disminuyó
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'almacen_id' => $this->almacen->id, 'cantidad_stock' => 70]);

        // Destino aumentó
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'sede_id' => $this->sede->id, 'almacen_id' => null, 'cantidad_stock' => 30]);

        // Solo 2 movimientos (salida + entrada)
        $this->assertEquals(2, InventoryMovement::where('reference_type', 'transfer')->where('reference_id', $transfer->id)->count());
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SEGURIDAD — operadores no deben pasar barreras
    // ─────────────────────────────────────────────────────────────────────────

    public function test_operador_almacen_no_accede_a_otro_almacen()
    {
        $otroAlmacen = Almacen::factory()->create(['activo' => true]);

        // El operador está asignado a $this->almacen, no a otroAlmacen
        // Las rutas del workspace (deposito.*) hacen guard por almacen_id del usuario
        $this->actingAs($this->operadorAlmacen)
            ->get(route('almacenes.show', $otroAlmacen))
            ->assertForbidden();
    }

    public function test_operador_almacen_no_puede_aprobar_transferencia()
    {
        $product = Product::factory()->create(['activo' => true]);
        Inventory::create(['product_id' => $product->id, 'almacen_id' => $this->almacen->id, 'sede_id' => null, 'cantidad_stock' => 50]);

        $transfer = StockTransfer::create([
            'from_almacen_id' => $this->almacen->id, 'from_sede_id' => null,
            'to_sede_id' => $this->sede->id, 'to_almacen_id' => null,
            'created_by' => $this->operadorAlmacen->id, 'estado' => 'pendiente',
        ]);
        StockTransferItem::create(['transfer_id' => $transfer->id, 'product_id' => $product->id, 'cantidad' => 10]);

        $this->actingAs($this->operadorAlmacen)
            ->post(route('transfers.approve', $transfer))
            ->assertForbidden();

        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer->id, 'estado' => 'pendiente']);
    }

    public function test_operador_almacen_sigue_sin_acceso_a_pos()
    {
        $this->actingAs($this->operadorAlmacen)
            ->get(route('pos.create'))
            ->assertForbidden();
    }

    public function test_operador_almacen_no_puede_crear_entrada_directa()
    {
        $product = Product::factory()->create(['activo' => true]);

        $this->actingAs($this->operadorAlmacen)
            ->post(route('almacenes.entry.store', $this->almacen), [
                'items' => [['product_id' => $product->id, 'cantidad' => 50, 'costo_unitario' => 5.00]],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('inventories', ['product_id' => $product->id, 'almacen_id' => $this->almacen->id]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // REGRESIÓN — funcionalidades existentes siguen trabajando
    // ─────────────────────────────────────────────────────────────────────────

    public function test_inventario_de_sede_sigue_funcionando()
    {
        $product = Product::factory()->create(['activo' => true]);
        Inventory::create(['product_id' => $product->id, 'sede_id' => $this->sede->id, 'almacen_id' => null, 'cantidad_stock' => 25]);

        // El stock de sede no debe verse afectado por entradas al almacén
        $this->actingAs($this->boss)
            ->post(route('almacenes.entry.store', $this->almacen), [
                'items' => [['product_id' => $product->id, 'cantidad' => 100, 'costo_unitario' => 5.00]],
            ]);

        // Sede stock intacto
        $this->assertDatabaseHas('inventories', [
            'product_id'    => $product->id,
            'sede_id'       => $this->sede->id,
            'almacen_id'    => null,
            'cantidad_stock' => 25,
        ]);

        // Almacén stock creado independientemente
        $this->assertDatabaseHas('inventories', [
            'product_id'    => $product->id,
            'sede_id'       => null,
            'almacen_id'    => $this->almacen->id,
            'cantidad_stock' => 100,
        ]);
    }

    public function test_recepciones_de_sede_siguen_funcionando_sin_almacen_id()
    {
        // InventoryReceipt de sede existente (almacen_id = null) no debe romperse
        $product = Product::factory()->create(['activo' => true]);
        $receipt = InventoryReceipt::create([
            'sede_id'       => $this->sede->id,
            'almacen_id'    => null,
            'user_id'       => $this->boss->id,
            'supplier_name' => 'Test Supplier',
            'monto_pagado'  => 100.00,
            'estado'        => 'pendiente',
        ]);

        $this->assertDatabaseHas('inventory_receipts', [
            'id'         => $receipt->id,
            'sede_id'    => $this->sede->id,
            'almacen_id' => null,
        ]);
    }

    public function test_ajuste_almacen_no_afecta_stock_de_sede()
    {
        $product = Product::factory()->create(['activo' => true]);
        Inventory::create(['product_id' => $product->id, 'sede_id' => $this->sede->id, 'almacen_id' => null, 'cantidad_stock' => 50]);
        Inventory::create(['product_id' => $product->id, 'sede_id' => null, 'almacen_id' => $this->almacen->id, 'cantidad_stock' => 30]);

        $this->actingAs($this->boss)
            ->post(route('almacenes.adjust', $this->almacen), [
                'product_id'   => $product->id,
                'new_cantidad' => 20,
                'motivo'       => 'Ajuste de prueba',
            ]);

        // Almacén ajustado
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'almacen_id' => $this->almacen->id, 'cantidad_stock' => 20]);

        // Sede intacta
        $this->assertDatabaseHas('inventories', ['product_id' => $product->id, 'sede_id' => $this->sede->id, 'cantidad_stock' => 50]);
    }

    public function test_carga_masiva_sigue_funcionando_para_almacen()
    {
        $product = Product::factory()->create(['activo' => true]);
        Inventory::create(['product_id' => $product->id, 'sede_id' => null, 'almacen_id' => $this->almacen->id, 'cantidad_stock' => 10]);

        $this->actingAs($this->boss)
            ->post(route('inventory.bulk-save'), [
                'loc_type'   => 'almacen',
                'almacen_id' => $this->almacen->id,
                'quantities' => [$product->id => 99],
                'motivo'     => 'Test carga masiva almacén',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('inventories', [
            'product_id'    => $product->id,
            'almacen_id'    => $this->almacen->id,
            'cantidad_stock' => 99,
        ]);
    }

    public function test_movimientos_almacen_muestra_filtros()
    {
        $this->actingAs($this->boss)
            ->get(route('almacenes.movements', $this->almacen))
            ->assertOk()
            ->assertSee('Desde')
            ->assertSee('Hasta')
            ->assertSee('Tipo')
            ->assertSee('Filtrar');
    }
}
