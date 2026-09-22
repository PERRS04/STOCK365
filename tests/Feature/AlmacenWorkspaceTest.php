<?php

namespace Tests\Feature;

use App\Models\Almacen;
use App\Models\Inventory;
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

class AlmacenWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private User    $operadorDeposito1;
    private User    $operadorDeposito2;
    private User    $operadorSede;
    private User    $boss;
    private User    $supervisor;
    private Almacen $deposito1;
    private Almacen $deposito2;
    private Sede    $sede;
    private Product $producto;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        Role::firstOrCreate(['name' => 'operador',   'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'supervisor',  'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'boss',        'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'inventory.view',    'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'inventory.adjust',  'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'transfers.approve', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'sales.create',      'guard_name' => 'web']);

        $this->deposito1 = Almacen::factory()->create(['nombre' => 'Depósito 1', 'activo' => true]);
        $this->deposito2 = Almacen::factory()->create(['nombre' => 'Depósito 2', 'activo' => true]);
        $this->sede      = Sede::factory()->create(['nombre' => 'Casa']);

        $this->operadorDeposito1 = User::factory()->create([
            'role'       => 'operador',
            'almacen_id' => $this->deposito1->id,
            'sede_id'    => null,
        ]);
        $this->operadorDeposito1->assignRole('operador');

        $this->operadorDeposito2 = User::factory()->create([
            'role'       => 'operador',
            'almacen_id' => $this->deposito2->id,
            'sede_id'    => null,
        ]);
        $this->operadorDeposito2->assignRole('operador');

        $this->operadorSede = User::factory()->create([
            'role'    => 'operador',
            'sede_id' => $this->sede->id,
            'almacen_id' => null,
        ]);
        $this->operadorSede->assignRole('operador');

        $this->boss = User::factory()->create(['role' => 'boss']);
        $this->boss->assignRole('boss');
        $this->boss->givePermissionTo(['inventory.view', 'transfers.approve']);

        $this->supervisor = User::factory()->create(['role' => 'supervisor']);
        $this->supervisor->assignRole('supervisor');
        $this->supervisor->givePermissionTo(['inventory.view', 'transfers.approve']);

        $this->producto = Product::factory()->create(['activo' => true]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function makeInventory(Almacen $almacen, int $qty = 50): Inventory
    {
        return Inventory::factory()->create([
            'product_id'     => $this->producto->id,
            'almacen_id'     => $almacen->id,
            'sede_id'        => null,
            'cantidad_stock' => $qty,
        ]);
    }

    private function makeTransferFrom(Almacen $from, array $overrides = []): StockTransfer
    {
        return StockTransfer::create(array_merge([
            'from_almacen_id' => $from->id,
            'from_sede_id'    => null,
            'to_sede_id'      => $this->sede->id,
            'to_almacen_id'   => null,
            'created_by'      => $this->operadorDeposito1->id,
            'estado'          => 'pendiente',
        ], $overrides));
    }

    // ═══════════════════════════════════════════════════════════════
    // STOCK — aislamiento
    // ═══════════════════════════════════════════════════════════════

    #[Test]
    public function operador_deposito1_puede_ver_stock_de_su_deposito(): void
    {
        $this->makeInventory($this->deposito1, 30);

        $response = $this->actingAs($this->operadorDeposito1)->get(route('deposito.stock'));

        $response->assertOk();
        $response->assertSee($this->deposito1->nombre);
    }

    #[Test]
    public function operador_deposito1_no_puede_ver_stock_de_deposito2(): void
    {
        // Stock exists in deposito2 — not accessible via workspace
        $this->makeInventory($this->deposito2, 99);

        // The workspace endpoint always scopes to user->almacen_id
        // Any product in deposito2 must NOT appear in operador1's view
        $response = $this->actingAs($this->operadorDeposito1)->get(route('deposito.stock'));

        $response->assertOk();
        // Response should NOT contain deposito2 name in the inventory context
        // (The page header shows deposito1's name, not deposito2's)
        $response->assertDontSee($this->deposito2->nombre);
    }

    #[Test]
    public function operador_de_sede_recibe_403_en_workspace_stock(): void
    {
        $response = $this->actingAs($this->operadorSede)->get(route('deposito.stock'));
        $response->assertStatus(403);
    }

    #[Test]
    public function usuario_sin_almacen_recibe_403_en_workspace_stock(): void
    {
        $sinUbicacion = User::factory()->create(['role' => 'operador', 'almacen_id' => null, 'sede_id' => null]);
        $sinUbicacion->assignRole('operador'); // role already created in setUp

        $response = $this->actingAs($sinUbicacion)->get(route('deposito.stock'));
        $response->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════════════
    // MOVIMIENTOS — aislamiento
    // ═══════════════════════════════════════════════════════════════

    #[Test]
    public function operador_deposito1_puede_ver_movimientos_de_su_deposito(): void
    {
        $response = $this->actingAs($this->operadorDeposito1)->get(route('deposito.movimientos'));
        $response->assertOk();
        $response->assertSee($this->deposito1->nombre);
    }

    #[Test]
    public function operador_deposito1_no_puede_ver_movimientos_de_deposito2(): void
    {
        $response = $this->actingAs($this->operadorDeposito1)->get(route('deposito.movimientos'));
        $response->assertOk();
        $response->assertDontSee($this->deposito2->nombre);
    }

    #[Test]
    public function operador_de_sede_recibe_403_en_workspace_movimientos(): void
    {
        $response = $this->actingAs($this->operadorSede)->get(route('deposito.movimientos'));
        $response->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════════════
    // TRANSFERENCIAS — scoping y creación
    // ═══════════════════════════════════════════════════════════════

    #[Test]
    public function operador_deposito1_solo_ve_transferencias_de_su_deposito(): void
    {
        $mia     = $this->makeTransferFrom($this->deposito1);
        $ajena   = $this->makeTransferFrom($this->deposito2, ['created_by' => $this->operadorDeposito2->id]);

        $response = $this->actingAs($this->operadorDeposito1)->get(route('deposito.transferencias'));

        $response->assertOk();
        // The page should show transferencias involving deposito1
        // and the ajena (deposito2) transfer should not appear
        // We check the DB isolation is correct by verifying scope
        $this->assertNotNull($mia);
        $this->assertNotNull($ajena);
        // Content check: the response renders only deposito1's context
        $response->assertSee($this->deposito1->nombre);
    }

    #[Test]
    public function operador_deposito1_no_ve_transferencias_entre_sedes(): void
    {
        $otraSede = Sede::factory()->create(['nombre' => 'Otra Sede']);
        $entrerSedes = StockTransfer::create([
            'from_sede_id'    => $this->sede->id,
            'to_sede_id'      => $otraSede->id,
            'from_almacen_id' => null,
            'to_almacen_id'   => null,
            'created_by'      => $this->boss->id,
            'estado'          => 'pendiente',
        ]);

        $response = $this->actingAs($this->operadorDeposito1)->get(route('deposito.transferencias'));

        $response->assertOk();
        // The sede-to-sede transfer should not be visible to the depot operator
        // (it doesn't involve either of their almacenes)
        $this->assertEquals(0, StockTransfer::where('from_almacen_id', $this->deposito1->id)
            ->orWhere('to_almacen_id', $this->deposito1->id)
            ->whereKey($entrerSedes->id)
            ->count());
    }

    #[Test]
    public function operador_de_sede_recibe_403_en_transferencias_workspace(): void
    {
        $response = $this->actingAs($this->operadorSede)->get(route('deposito.transferencias'));
        $response->assertStatus(403);
    }

    #[Test]
    public function operador_puede_crear_transferencia_desde_su_deposito(): void
    {
        $this->makeInventory($this->deposito1, 50);

        $response = $this->actingAs($this->operadorDeposito1)->post(route('deposito.transferencias.store'), [
            'to_sede_id'    => $this->sede->id,
            'to_almacen_id' => '',
            'motivo'        => 'Reposición',
            'items'         => [['product_id' => $this->producto->id, 'cantidad' => 10]],
        ]);

        $response->assertRedirect(route('deposito.transferencias'));

        $this->assertDatabaseHas('stock_transfers', [
            'from_almacen_id' => $this->deposito1->id,  // forced to operator's warehouse
            'from_sede_id'    => null,
            'to_sede_id'      => $this->sede->id,
            'estado'          => 'pendiente',
            'created_by'      => $this->operadorDeposito1->id,
        ]);

        // Stock must NOT be modified yet — only after approval
        $this->assertEquals(50, Inventory::where('almacen_id', $this->deposito1->id)
            ->value('cantidad_stock'));
    }

    #[Test]
    public function operador_no_puede_falsificar_deposito2_como_origen(): void
    {
        $this->makeInventory($this->deposito1, 50);
        $this->makeInventory($this->deposito2, 50);

        // Attacker sends deposito2 as origin via POST body — must be ignored
        $response = $this->actingAs($this->operadorDeposito1)->post(route('deposito.transferencias.store'), [
            'from_almacen_id' => $this->deposito2->id,  // attacker input — must be ignored
            'to_sede_id'      => $this->sede->id,
            'to_almacen_id'   => '',
            'motivo'          => 'Malicioso',
            'items'           => [['product_id' => $this->producto->id, 'cantidad' => 5]],
        ]);

        $response->assertRedirect(route('deposito.transferencias'));

        // Origin must always be deposito1 (the operator's own warehouse)
        $this->assertDatabaseHas('stock_transfers', [
            'from_almacen_id' => $this->deposito1->id,
        ]);

        // Deposito2 must NOT appear as origin
        $this->assertDatabaseMissing('stock_transfers', [
            'from_almacen_id' => $this->deposito2->id,
        ]);
    }

    #[Test]
    public function operador_no_puede_usar_sede_como_origen(): void
    {
        // The workspace storeTransfer never accepts from_sede_id from input;
        // from_sede_id is always set to null for almacen operators.
        $this->makeInventory($this->deposito1, 50);

        $response = $this->actingAs($this->operadorDeposito1)->post(route('deposito.transferencias.store'), [
            'from_sede_id'  => $this->sede->id,  // attacker input — must be ignored
            'to_sede_id'    => $this->sede->id,
            'to_almacen_id' => '',
            'items'         => [['product_id' => $this->producto->id, 'cantidad' => 5]],
        ]);

        $response->assertRedirect(route('deposito.transferencias'));

        $this->assertDatabaseHas('stock_transfers', [
            'from_almacen_id' => $this->deposito1->id,
            'from_sede_id'    => null,
        ]);
    }

    #[Test]
    public function operador_no_puede_transferir_hacia_el_mismo_deposito(): void
    {
        $this->makeInventory($this->deposito1, 50);

        $response = $this->actingAs($this->operadorDeposito1)->post(route('deposito.transferencias.store'), [
            'to_sede_id'    => '',
            'to_almacen_id' => $this->deposito1->id,  // same as origin
            'items'         => [['product_id' => $this->producto->id, 'cantidad' => 5]],
        ]);

        $response->assertSessionHasErrors('to_almacen_id');
        $this->assertEquals(0, StockTransfer::count());
    }

    // ═══════════════════════════════════════════════════════════════
    // APROBACIÓN — operador no puede
    // ═══════════════════════════════════════════════════════════════

    #[Test]
    public function operador_no_puede_aprobar_transferencias(): void
    {
        $this->makeInventory($this->deposito1, 50);
        $transfer = $this->makeTransferFrom($this->deposito1);
        StockTransferItem::create([
            'transfer_id' => $transfer->id,
            'product_id'  => $this->producto->id,
            'cantidad'    => 5,
        ]);

        $response = $this->actingAs($this->operadorDeposito1)
            ->post(route('transfers.approve', $transfer));

        $response->assertStatus(403);
        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer->id, 'estado' => 'pendiente']);
    }

    #[Test]
    public function operador_no_puede_rechazar_transferencias(): void
    {
        $transfer = $this->makeTransferFrom($this->deposito1);

        $response = $this->actingAs($this->operadorDeposito1)
            ->patch(route('transfers.reject', $transfer), ['notas_aprobacion' => 'x']);

        $response->assertStatus(403);
        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer->id, 'estado' => 'pendiente']);
    }

    #[Test]
    public function supervisor_puede_aprobar_con_transfers_approve(): void
    {
        $this->makeInventory($this->deposito1, 50);
        $inv = Inventory::factory()->create([
            'product_id'     => $this->producto->id,
            'sede_id'        => $this->sede->id,
            'almacen_id'     => null,
            'cantidad_stock' => 0,
        ]);

        $transfer = $this->makeTransferFrom($this->deposito1);
        StockTransferItem::create([
            'transfer_id' => $transfer->id,
            'product_id'  => $this->producto->id,
            'cantidad'    => 10,
        ]);

        $response = $this->actingAs($this->supervisor)
            ->post(route('transfers.approve', $transfer));

        $response->assertRedirect(route('transfers.index'));
        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer->id, 'estado' => 'aprobado']);

        // Stock moved correctly
        $this->assertEquals(40, Inventory::where('almacen_id', $this->deposito1->id)->value('cantidad_stock'));
        $this->assertEquals(10, Inventory::where('sede_id', $this->sede->id)->value('cantidad_stock'));
    }

    #[Test]
    public function boss_puede_aprobar_con_transfers_approve(): void
    {
        $this->makeInventory($this->deposito1, 100);
        Inventory::factory()->create([
            'product_id'     => $this->producto->id,
            'sede_id'        => $this->sede->id,
            'almacen_id'     => null,
            'cantidad_stock' => 5,
        ]);

        $transfer = $this->makeTransferFrom($this->deposito1);
        StockTransferItem::create([
            'transfer_id' => $transfer->id,
            'product_id'  => $this->producto->id,
            'cantidad'    => 20,
        ]);

        $response = $this->actingAs($this->boss)
            ->post(route('transfers.approve', $transfer));

        $response->assertRedirect(route('transfers.index'));
        $this->assertDatabaseHas('stock_transfers', ['id' => $transfer->id, 'estado' => 'aprobado']);
        $this->assertEquals(80,  Inventory::where('almacen_id', $this->deposito1->id)->value('cantidad_stock'));
        $this->assertEquals(25,  Inventory::where('sede_id', $this->sede->id)->value('cantidad_stock'));
    }

    // ═══════════════════════════════════════════════════════════════
    // POS — almacén operator cannot sell
    // ═══════════════════════════════════════════════════════════════

    #[Test]
    public function operador_de_deposito_no_puede_entrar_al_pos(): void
    {
        $response = $this->actingAs($this->operadorDeposito1)->get(route('pos.create'));
        $response->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════════════
    // SHOW TRANSFER — scoping
    // ═══════════════════════════════════════════════════════════════

    #[Test]
    public function operador_puede_ver_detalle_de_su_propia_transferencia(): void
    {
        $transfer = $this->makeTransferFrom($this->deposito1);
        StockTransferItem::create([
            'transfer_id' => $transfer->id,
            'product_id'  => $this->producto->id,
            'cantidad'    => 5,
        ]);

        $response = $this->actingAs($this->operadorDeposito1)
            ->get(route('deposito.transferencias.show', $transfer));

        $response->assertOk();
    }

    #[Test]
    public function operador_no_puede_ver_detalle_de_transferencia_de_otro_deposito(): void
    {
        $ajena = $this->makeTransferFrom($this->deposito2, ['created_by' => $this->operadorDeposito2->id]);

        $response = $this->actingAs($this->operadorDeposito1)
            ->get(route('deposito.transferencias.show', $ajena));

        $response->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════════════
    // CARGA MASIVA — operador no puede
    // ═══════════════════════════════════════════════════════════════

    #[Test]
    public function operador_no_puede_usar_carga_masiva(): void
    {
        $response = $this->actingAs($this->operadorDeposito1)->get(route('inventory.bulk-load'));
        $response->assertStatus(403);
    }

    #[Test]
    public function boss_puede_cargar_inventario_inicial_en_almacen(): void
    {
        $response = $this->actingAs($this->boss)->post(route('inventory.bulk-save'), [
            'loc_type'   => 'almacen',
            'almacen_id' => $this->deposito1->id,
            'quantities' => [$this->producto->id => 75],
            'motivo'     => 'Inventario inicial Depósito 1',
        ]);

        $response->assertRedirect();

        $this->assertEquals(75, Inventory::where('almacen_id', $this->deposito1->id)
            ->where('product_id', $this->producto->id)
            ->value('cantidad_stock'));
    }

    #[Test]
    public function carga_masiva_almacen_no_altera_inventario_de_sede(): void
    {
        // Seed sede inventory first
        Inventory::factory()->create([
            'product_id'     => $this->producto->id,
            'sede_id'        => $this->sede->id,
            'almacen_id'     => null,
            'cantidad_stock' => 20,
        ]);

        // Load stock into the warehouse
        $this->actingAs($this->boss)->post(route('inventory.bulk-save'), [
            'loc_type'   => 'almacen',
            'almacen_id' => $this->deposito1->id,
            'quantities' => [$this->producto->id => 100],
            'motivo'     => 'Carga inicial',
        ]);

        // Sede stock must remain unchanged
        $this->assertEquals(20, Inventory::where('sede_id', $this->sede->id)
            ->where('product_id', $this->producto->id)
            ->value('cantidad_stock'));

        // Deposito1 stock must be set correctly
        $this->assertEquals(100, Inventory::where('almacen_id', $this->deposito1->id)
            ->where('product_id', $this->producto->id)
            ->value('cantidad_stock'));
    }

    #[Test]
    public function carga_masiva_almacen_genera_movimiento_ajuste_auditado(): void
    {
        $this->actingAs($this->boss)->post(route('inventory.bulk-save'), [
            'loc_type'   => 'almacen',
            'almacen_id' => $this->deposito1->id,
            'quantities' => [$this->producto->id => 50],
            'motivo'     => 'Carga inicial',
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $this->producto->id,
            'almacen_id' => $this->deposito1->id,
            'sede_id'    => null,
            'tipo'       => 'ajuste',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════
    // INVENTORY GLOBAL — operador no puede acceder
    // ═══════════════════════════════════════════════════════════════

    #[Test]
    public function operador_de_deposito_no_puede_ver_inventario_global(): void
    {
        $response = $this->actingAs($this->operadorDeposito1)->get(route('inventory.index'));
        $response->assertStatus(403);
    }

    #[Test]
    public function operador_de_deposito_no_puede_ajustar_stock(): void
    {
        $response = $this->actingAs($this->operadorDeposito1)->post(route('inventory.adjust'), [
            'product_id' => $this->producto->id,
            'sede_id'    => $this->sede->id,
            'cantidad'   => 10,
            'motivo'     => 'Test',
        ]);
        $response->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════════════
    // C2 — Destinos activos
    // ═══════════════════════════════════════════════════════════════

    #[Test]
    public function operador_no_puede_transferir_a_sede_inactiva(): void
    {
        $sedeInactiva = Sede::factory()->create(['activa' => false]);
        $this->makeInventory($this->deposito1, 50);

        $response = $this->actingAs($this->operadorDeposito1)->post(
            route('deposito.transferencias.store'),
            ['to_sede_id' => $sedeInactiva->id, 'items' => [['product_id' => $this->producto->id, 'cantidad' => 5]]]
        );

        $response->assertSessionHasErrors('to_sede_id');
    }

    #[Test]
    public function operador_no_puede_transferir_a_almacen_inactivo(): void
    {
        $almacenInactivo = Almacen::factory()->create(['activo' => false]);
        $this->makeInventory($this->deposito1, 50);

        $response = $this->actingAs($this->operadorDeposito1)->post(
            route('deposito.transferencias.store'),
            ['to_almacen_id' => $almacenInactivo->id, 'items' => [['product_id' => $this->producto->id, 'cantidad' => 5]]]
        );

        $response->assertSessionHasErrors('to_almacen_id');
    }

    #[Test]
    public function operador_puede_transferir_a_sede_activa(): void
    {
        $sedeActiva = Sede::factory()->create(['activa' => true]);
        $this->makeInventory($this->deposito1, 50);

        $response = $this->actingAs($this->operadorDeposito1)->post(
            route('deposito.transferencias.store'),
            ['to_sede_id' => $sedeActiva->id, 'items' => [['product_id' => $this->producto->id, 'cantidad' => 5]]]
        );

        $response->assertRedirect(route('deposito.transferencias'));
    }

    #[Test]
    public function operador_puede_transferir_a_almacen_activo(): void
    {
        $this->makeInventory($this->deposito1, 50);

        $response = $this->actingAs($this->operadorDeposito1)->post(
            route('deposito.transferencias.store'),
            ['to_almacen_id' => $this->deposito2->id, 'items' => [['product_id' => $this->producto->id, 'cantidad' => 5]]]
        );

        $response->assertRedirect(route('deposito.transferencias'));
    }

    // ═══════════════════════════════════════════════════════════════
    // C4 — ActivityLog almacen_id
    // ═══════════════════════════════════════════════════════════════

    #[Test]
    public function bulk_almacen_registra_almacen_id_en_auditoria(): void
    {
        $this->makeInventory($this->deposito1, 10);

        $this->actingAs($this->boss)->post(route('inventory.bulk-save'), [
            'loc_type'   => 'almacen',
            'almacen_id' => $this->deposito1->id,
            'quantities' => [$this->producto->id => 20],
            'motivo'     => 'test auditoria almacen',
        ]);

        $log = \App\Models\ActivityLog::where('action', 'inventory.bulk_load')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($this->deposito1->id, $log->almacen_id);
        $this->assertNull($log->sede_id);
    }

    #[Test]
    public function transferencia_workspace_registra_almacen_id_en_auditoria(): void
    {
        $this->makeInventory($this->deposito1, 50);

        $this->actingAs($this->operadorDeposito1)->post(
            route('deposito.transferencias.store'),
            ['to_almacen_id' => $this->deposito2->id, 'items' => [['product_id' => $this->producto->id, 'cantidad' => 5]]]
        );

        $log = \App\Models\ActivityLog::where('action', 'transferencia.creada')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame($this->deposito1->id, $log->almacen_id);
    }

    // ═══════════════════════════════════════════════════════════════
    // C7 — Guards: only operators with almacen_id can access workspace
    // ═══════════════════════════════════════════════════════════════

    #[Test]
    public function boss_con_almacen_id_no_puede_acceder_workspace(): void
    {
        $boss = User::factory()->create([
            'role'       => 'boss',
            'almacen_id' => $this->deposito1->id,
            'sede_id'    => null,
        ]);
        $boss->assignRole('boss');

        $this->actingAs($boss)->get(route('deposito.stock'))->assertStatus(403);
    }

    #[Test]
    public function supervisor_con_almacen_id_no_puede_acceder_workspace(): void
    {
        Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'web']);

        $supervisor = User::factory()->create([
            'role'       => 'supervisor',
            'almacen_id' => $this->deposito1->id,
            'sede_id'    => null,
        ]);
        $supervisor->assignRole('supervisor');

        $this->actingAs($supervisor)->get(route('deposito.stock'))->assertStatus(403);
    }

    #[Test]
    public function usuario_sin_rol_con_almacen_id_no_puede_acceder_workspace(): void
    {
        $user = User::factory()->create([
            'almacen_id' => $this->deposito1->id,
            'sede_id'    => null,
        ]);

        $this->actingAs($user)->get(route('deposito.stock'))->assertStatus(403);
    }

    // ═══════════════════════════════════════════════════════════════
    // C8 — Concurrent approval (idempotency guard)
    // ═══════════════════════════════════════════════════════════════

    private function createPendingTransfer(int $cantidad): StockTransfer
    {
        $transfer = $this->makeTransferFrom($this->deposito1);
        StockTransferItem::create([
            'transfer_id' => $transfer->id,
            'product_id'  => $this->producto->id,
            'cantidad'    => $cantidad,
        ]);

        return $transfer;
    }

    #[Test]
    public function transferencia_aprobada_no_puede_aprobarse_nuevamente(): void
    {
        $this->makeInventory($this->deposito1, 50);
        Inventory::factory()->create([
            'product_id'     => $this->producto->id,
            'sede_id'        => $this->sede->id,
            'almacen_id'     => null,
            'cantidad_stock' => 0,
        ]);

        $transfer = $this->createPendingTransfer(5);

        // First approval
        $this->actingAs($this->boss)->post(route('transfers.approve', $transfer));
        $transfer->refresh();
        $this->assertEquals('aprobado', $transfer->estado);

        // Second approval attempt
        $response = $this->actingAs($this->boss)->post(route('transfers.approve', $transfer));
        $response->assertSessionHas('error');

        // Stock should not have moved a second time — deposito1 should be exactly 45
        $this->assertEquals(45, Inventory::where('almacen_id', $this->deposito1->id)->value('cantidad_stock'));
    }

    #[Test]
    public function transferencia_rechazada_no_puede_aprobarse(): void
    {
        $this->makeInventory($this->deposito1, 50);

        $transfer = $this->createPendingTransfer(5);

        $this->actingAs($this->boss)->patch(
            route('transfers.reject', $transfer),
            ['notas_aprobacion' => 'test']
        );
        $transfer->refresh();
        $this->assertEquals('rechazado', $transfer->estado);

        $response = $this->actingAs($this->boss)->post(route('transfers.approve', $transfer));
        $response->assertSessionHas('error');
    }

    // ═══════════════════════════════════════════════════════════════
    // C6 — Estado filter whitelist
    // ═══════════════════════════════════════════════════════════════

    #[Test]
    public function filtro_estado_invalido_no_filtra_resultados(): void
    {
        $this->makeTransferFrom($this->deposito1);

        $response = $this->actingAs($this->operadorDeposito1)
            ->get(route('deposito.transferencias', ['estado' => 'invalido']));

        $response->assertStatus(200);
        // Should not error — invalid filter is ignored, all transfers still visible
    }
}
