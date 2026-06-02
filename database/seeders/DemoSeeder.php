<?php

namespace Database\Seeders;

use App\Models\CashClosing;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\CourtesyTransaction;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Provider;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Sede;
use App\Models\StockAlert;
use App\Models\User;
use App\Models\WorkforcePerformanceScore;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Sede DEMO CENTER ──────────────────────────────────────────────
        $sede = Sede::updateOrCreate(
            ['nombre' => 'DEMO CENTER'],
            [
                'ciudad'    => 'Guayaquil',
                'ubicacion' => 'Av. Francisco de Orellana, World Trade Center Torre B',
                'telefono'  => '04-9000000',
                'email'     => 'demo@stock365.com',
                'activa'    => true,
                'is_demo'   => true,
            ]
        );

        Cache::forget('sede_demo_ids');

        // ── 2. Usuarios demo ─────────────────────────────────────────────────
        $boss = User::updateOrCreate(
            ['email' => 'demo-boss@stock365.com'],
            [
                'name'              => 'Demo Boss',
                'password'          => Hash::make('Demo123456'),
                'role'              => 'boss',
                'sede_id'           => null,
                'active'            => true,
                'email_verified_at' => now(),
            ]
        );
        $boss->syncRoles(['boss']);

        $supervisor = User::updateOrCreate(
            ['email' => 'demo-supervisor@stock365.com'],
            [
                'name'              => 'Demo Supervisor',
                'password'          => Hash::make('Demo123456'),
                'role'              => 'supervisor',
                'sede_id'           => $sede->id,
                'active'            => true,
                'email_verified_at' => now(),
            ]
        );
        $supervisor->syncRoles(['supervisor']);

        $operador = User::updateOrCreate(
            ['email' => 'demo-operador@stock365.com'],
            [
                'name'              => 'Demo Operadora',
                'password'          => Hash::make('Demo123456'),
                'role'              => 'operador',
                'sede_id'           => $sede->id,
                'active'            => true,
                'email_verified_at' => now(),
            ]
        );
        $operador->syncRoles(['operador']);

        // ── 3. Productos (catálogo global) ───────────────────────────────────
        $this->seedProducts($boss->id);
        $products = Product::where('activo', true)->pluck('id')->toArray();

        // ── 4. Proveedores demo ──────────────────────────────────────────────
        $this->seedProviders($boss->id);

        // ── 5. Inventario para DEMO CENTER ───────────────────────────────────
        $this->seedInventory($sede->id, $products);

        // ── 6. Cash sessions históricas + ventas ─────────────────────────────
        $this->seedHistoricalSessions($sede, $operador, $supervisor, $boss, $products);

        // ── 7. Sesión activa de hoy ──────────────────────────────────────────
        $activeSession = $this->seedActiveSession($sede, $operador, $products);

        // ── 8. Movimiento de caja pendiente ───────────────────────────────────
        $this->seedPendingMovement($sede, $operador, $activeSession);

        // ── 9. Cortesía pendiente ─────────────────────────────────────────────
        $this->seedPendingCourtesy($sede, $operador, $products);

        // ── 10. Stock alerts demo ─────────────────────────────────────────────
        $this->seedStockAlerts($sede, $products);

        // ── 11. Workforce performance scores ─────────────────────────────────
        $this->seedWorkforceScores($sede, $operador);
    }

    // ── Product catalog ─────────────────────────────────────────────────────

    private function seedProducts(int $bossId): void
    {
        $extra = [
            ['sku' => 'HEI-330', 'nombre' => 'Heineken',         'marca' => 'Heineken',   'tamaño' => '330ml', 'precio_compra' => 1.40, 'precio_venta' => 2.00, 'stock_minimo' => 24],
            ['sku' => 'BUD-355', 'nombre' => 'Budweiser',        'marca' => 'Budweiser',  'tamaño' => '355ml', 'precio_compra' => 1.20, 'precio_venta' => 1.75, 'stock_minimo' => 24],
            ['sku' => 'STE-330', 'nombre' => 'Stella Artois',    'marca' => 'Stella',     'tamaño' => '330ml', 'precio_compra' => 1.60, 'precio_venta' => 2.25, 'stock_minimo' => 24],
            ['sku' => 'AGU-DAS', 'nombre' => 'Agua Dasani',      'marca' => 'Dasani',     'tamaño' => '600ml', 'precio_compra' => 0.40, 'precio_venta' => 0.75, 'stock_minimo' => 48],
            ['sku' => 'AGU-GAS', 'nombre' => 'Aquarius',         'marca' => 'Aquarius',   'tamaño' => '500ml', 'precio_compra' => 0.50, 'precio_venta' => 0.85, 'stock_minimo' => 24],
            ['sku' => 'COC-600', 'nombre' => 'Coca-Cola',        'marca' => 'Coca-Cola',  'tamaño' => '600ml', 'precio_compra' => 0.60, 'precio_venta' => 1.00, 'stock_minimo' => 48],
            ['sku' => 'SPR-600', 'nombre' => 'Sprite',           'marca' => 'Sprite',     'tamaño' => '600ml', 'precio_compra' => 0.55, 'precio_venta' => 0.90, 'stock_minimo' => 24],
            ['sku' => 'RED-250', 'nombre' => 'Red Bull',         'marca' => 'Red Bull',   'tamaño' => '250ml', 'precio_compra' => 1.80, 'precio_venta' => 2.50, 'stock_minimo' => 12],
            ['sku' => 'GTO-330', 'nombre' => 'Güitig',           'marca' => 'Güitig',     'tamaño' => '330ml', 'precio_compra' => 0.45, 'precio_venta' => 0.75, 'stock_minimo' => 24],
            ['sku' => 'MAR-020', 'nombre' => 'Marlboro Rojo',    'marca' => 'Marlboro',   'tamaño' => 'Pack',  'precio_compra' => 1.20, 'precio_venta' => 1.75, 'stock_minimo' => 10],
            ['sku' => 'MAR-MEN', 'nombre' => 'Marlboro Menthol', 'marca' => 'Marlboro',   'tamaño' => 'Pack',  'precio_compra' => 1.20, 'precio_venta' => 1.75, 'stock_minimo' => 10],
            ['sku' => 'FDC-750', 'nombre' => 'Flor de Caña 7',  'marca' => 'Flor de Caña','tamaño' => '750ml', 'precio_compra' => 12.00,'precio_venta' => 18.00,'stock_minimo' => 6],
            ['sku' => 'SMI-750', 'nombre' => 'Smirnoff Vodka',  'marca' => 'Smirnoff',   'tamaño' => '750ml', 'precio_compra' => 10.00,'precio_venta' => 15.00,'stock_minimo' => 6],
            ['sku' => 'JDN-700', 'nombre' => "Jack Daniel's",   'marca' => "Jack Daniel's",'tamaño' => '700ml','precio_compra' => 22.00,'precio_venta' => 32.00,'stock_minimo' => 3],
            ['sku' => 'HIE-KG',  'nombre' => 'Hielo Premium',   'marca' => 'Premium',    'tamaño' => '1kg',   'precio_compra' => 0.30, 'precio_venta' => 0.50, 'stock_minimo' => 20],
        ];

        foreach ($extra as $data) {
            Product::firstOrCreate(
                ['sku' => $data['sku']],
                $data + ['activo' => true, 'created_by' => $bossId]
            );
        }
    }

    // ── Providers ────────────────────────────────────────────────────────────

    private function seedProviders(int $bossId): void
    {
        $providers = [
            ['nombre' => 'Cervecería Nacional S.A.',    'ruc_nit' => '0990001234001', 'telefono' => '04-3720000', 'contacto_principal' => 'Carlos Méndez',    'email' => 'ventas@cn.com.ec'],
            ['nombre' => 'Distribuidora Beverages EC',  'ruc_nit' => '0990005678001', 'telefono' => '04-2561000', 'contacto_principal' => 'Patricia Vásquez',  'email' => 'pedidos@beverages.ec'],
            ['nombre' => 'Tabaco & Cía. S.A.',          'ruc_nit' => '0990009012001', 'telefono' => '04-6002000', 'contacto_principal' => 'Roberto Alvarado',  'email' => 'ventas@tabaco.ec'],
        ];

        foreach ($providers as $data) {
            Provider::firstOrCreate(
                ['ruc_nit' => $data['ruc_nit']],
                $data + ['activo' => true, 'created_by' => $bossId]
            );
        }
    }

    // ── Inventory ────────────────────────────────────────────────────────────

    private function seedInventory(int $sedeId, array $productIds): void
    {
        $stockLevels = [
            'COR-355' => 144, 'COR-ITA' => 120, 'PIL-630' => 192, 'PIL-LIT' => 168,
            'CLV-600' => 96,  'CLB-PLA' => 84,  'BIE-RES' => 108, 'BIE-LIT' => 96,
            'SIE-LAT' => 72,  'CAR-330' => 240, 'HEI-330' => 60,  'BUD-355' => 72,
            'STE-330' => 48,  'AGU-DAS' => 180, 'AGU-GAS' => 96,  'COC-600' => 144,
            'SPR-600' => 120, 'RED-250' => 36,  'GTO-330' => 7,   // bajo stock → alerta
            'MAR-020' => 4,   // bajo stock → alerta
            'MAR-MEN' => 18,  'FDC-750' => 12,  'SMI-750' => 10,
            'JDN-700' => 6,   'HIE-KG'  => 30,
        ];

        $products = Product::whereIn('id', $productIds)->get()->keyBy('sku');

        foreach ($products as $sku => $product) {
            $stock = $stockLevels[$sku] ?? 48;
            Inventory::updateOrCreate(
                ['product_id' => $product->id, 'sede_id' => $sedeId],
                [
                    'cantidad_stock'      => $stock,
                    'stock_recomendado'   => max($product->stock_minimo, 48),
                    'ultima_actualizacion'=> now()->subHours(rand(1, 24)),
                ]
            );
        }
    }

    // ── Historical cash sessions + sales ─────────────────────────────────────

    private function seedHistoricalSessions(Sede $sede, User $operador, User $supervisor, User $boss, array $productIds): void
    {
        // Sales per day: [daysAgo => [numSales, openingAmount]]
        $days = [
            7 => [10, 200.00],
            6 => [13, 312.50],
            5 => [16, 287.75],
            4 => [12, 198.00],
            3 => [19, 350.00],
            2 => [21, 287.50],
            1 => [23, 387.50], // yesterday — closing approved
        ];

        $products = Product::whereIn('id', $productIds)->where('activo', true)->get();

        foreach ($days as $daysAgo => [$numSales, $openingAmount]) {
            $dayStart = now()->subDays($daysAgo)->setTime(8, 0, 0);
            $dayEnd   = now()->subDays($daysAgo)->setTime(22, 30, 0);

            // Skip if session already exists for this day
            $exists = CashSession::where('sede_id', $sede->id)
                ->where('user_id', $operador->id)
                ->whereDate('opened_at', $dayStart->toDateString())
                ->exists();
            if ($exists) continue;

            $session = CashSession::create([
                'user_id'        => $operador->id,
                'sede_id'        => $sede->id,
                'opening_amount' => $openingAmount,
                'status'         => 'closed',
                'opened_at'      => $dayStart,
                'closed_at'      => $dayEnd,
                'notes'          => 'Turno demo',
            ]);

            // Create sales for this session
            $totalVentas = 0;
            for ($i = 0; $i < $numSales; $i++) {
                $saleTime  = $dayStart->copy()->addMinutes(rand(30, (int) $dayStart->diffInMinutes($dayEnd) - 10));
                $saleTotal = $this->createSale($session, $sede, $operador, $products, $saleTime);
                $totalVentas += $saleTotal;
            }

            // Create closing for this session (approved)
            $efectivo   = $openingAmount + $totalVentas;
            $diferencia = 0;
            CashClosing::create([
                'sede_id'          => $sede->id,
                'user_id'          => $operador->id,
                'cash_session_id'  => $session->id,
                'monto_inicial'    => $openingAmount,
                'fecha_cierre'     => $dayEnd->toDateString(),
                'total_sistema'    => round($totalVentas, 2),
                'efectivo'         => round($efectivo, 2),
                'saldo_final'      => round($efectivo, 2),
                'transferencias'   => 0,
                'cheques'          => 0,
                'diferencia'       => $diferencia,
                'observaciones'    => 'Cierre de turno demo · Todo correcto.',
                'estado'           => 'aprobado',
                'approved_by'      => $supervisor->id,
                'approved_at'      => $dayEnd->addMinutes(15),
                'created_at'       => $dayEnd,
                'updated_at'       => $dayEnd->addMinutes(15),
            ]);

            // Update session with inherited amount for next day
            if ($daysAgo === 1) {
                $session->update(['status' => 'closed']);
            }
        }
    }

    private function createSale(CashSession $session, Sede $sede, User $operador, $products, Carbon $saleTime): float
    {
        $numItems  = rand(1, 3);
        $items     = $products->random(min($numItems, $products->count()));
        $total     = 0;
        $descuento = rand(0, 10) === 0 ? round(rand(100, 300) / 100, 2) : 0;

        $sale = Sale::create([
            'sede_id'         => $sede->id,
            'user_id'         => $operador->id,
            'cash_session_id' => $session->id,
            'total_sistema'   => 0, // updated after items
            'descuento'       => $descuento,
            'estado'          => 'completada',
            'fecha_venta'     => $saleTime,
            'created_at'      => $saleTime,
            'updated_at'      => $saleTime,
        ]);

        foreach ($items as $product) {
            $qty      = rand(1, 4);
            $subtotal = round($product->precio_venta * $qty, 2);
            $total   += $subtotal;

            SaleItem::create([
                'sale_id'          => $sale->id,
                'product_id'       => $product->id,
                'cantidad'         => $qty,
                'precio_unitario'  => $product->precio_venta,
                'costo_unitario'   => $product->precio_compra,
                'subtotal'         => $subtotal,
            ]);
        }

        $finalTotal = max(0, round($total - $descuento, 2));
        $sale->update(['total_sistema' => $finalTotal]);

        return $finalTotal;
    }

    // ── Active session (today) ────────────────────────────────────────────────

    private function seedActiveSession(Sede $sede, User $operador, array $productIds): CashSession
    {
        $existing = CashSession::where('sede_id', $sede->id)
            ->where('user_id', $operador->id)
            ->whereIn('status', ['open', 'pending_closing'])
            ->first();

        if ($existing) return $existing;

        $openedAt = now()->setTime(8, 0, 0);
        $session  = CashSession::create([
            'user_id'        => $operador->id,
            'sede_id'        => $sede->id,
            'opening_amount' => 387.50, // inherited from yesterday
            'status'         => 'open',
            'opened_at'      => $openedAt,
            'notes'          => 'Turno demo activo',
        ]);

        // Today's sales so far
        $products = Product::whereIn('id', $productIds)->where('activo', true)->get();
        $numSales = rand(5, 9);
        for ($i = 0; $i < $numSales; $i++) {
            $saleTime = $openedAt->copy()->addMinutes(rand(5, (int) $openedAt->diffInMinutes(now()) - 5));
            $this->createSale($session, $sede, $operador, $products, $saleTime);
        }

        return $session;
    }

    // ── Pending cash movement ─────────────────────────────────────────────────

    private function seedPendingMovement(Sede $sede, User $operador, CashSession $session): void
    {
        $exists = CashMovement::where('sede_id', $sede->id)
            ->where('status', 'pendiente')
            ->where('type', 'deposito')
            ->exists();
        if ($exists) return;

        CashMovement::create([
            'sede_id'         => $sede->id,
            'user_id'         => $operador->id,
            'cash_session_id' => $session->id,
            'type'            => 'deposito',
            'amount'          => 200.00,
            'motivo'          => 'Depósito de cambio',
            'observaciones'   => 'Retiro de $200 para cambio. Solicito aprobación.',
            'attachment_path' => '',
            'status'          => 'pendiente',
            'created_at'      => now()->subMinutes(rand(35, 90)),
        ]);
    }

    // ── Pending courtesy ─────────────────────────────────────────────────────

    private function seedPendingCourtesy(Sede $sede, User $operador, array $productIds): void
    {
        $exists = CourtesyTransaction::where('sede_id', $sede->id)
            ->where('status', 'pendiente')
            ->exists();
        if ($exists) return;

        $coronaId = Product::where('sku', 'COR-355')->value('id')
            ?? collect($productIds)->first();

        CourtesyTransaction::create([
            'sede_id'         => $sede->id,
            'user_id'         => $operador->id,
            'product_id'      => $coronaId,
            'quantity'        => 2,
            'tipo'            => 'apostador_vip',
            'motivo'          => 'Cliente VIP · Mesa 5',
            'cliente_nombre'  => 'Cliente Frecuente',
            'observaciones'   => 'Cliente recurrente, mesa 5.',
            'attachment_path' => '',
            'status'          => 'pendiente',
            'created_at'      => now()->subMinutes(rand(20, 50)),
        ]);
    }

    // ── Stock alerts ─────────────────────────────────────────────────────────

    private function seedStockAlerts(Sede $sede, array $productIds): void
    {
        $lowStock = [
            ['sku' => 'GTO-330', 'actual' => 7,  'minimo' => 24],
            ['sku' => 'MAR-020', 'actual' => 4,  'minimo' => 10],
        ];

        foreach ($lowStock as $item) {
            $product = Product::where('sku', $item['sku'])->first();
            if (!$product) continue;

            StockAlert::updateOrCreate(
                ['product_id' => $product->id, 'sede_id' => $sede->id],
                [
                    'stock_actual'    => $item['actual'],
                    'stock_minimo'    => $item['minimo'],
                    'alerta_activa'   => true,
                    'fecha_alerta'    => now()->subHours(rand(2, 8)),
                    'notificacion_enviada' => false,
                ]
            );
        }
    }

    // ── Workforce performance scores ──────────────────────────────────────────

    private function seedWorkforceScores(Sede $sede, User $operador): void
    {
        $weeks = [
            ['start' => now()->subWeeks(2)->startOfWeek()->toDateString(), 'score' => 68, 'label' => 'Developing'],
            ['start' => now()->subWeeks(1)->startOfWeek()->toDateString(), 'score' => 72, 'label' => 'Solid'],
            ['start' => now()->startOfWeek()->toDateString(),              'score' => 76, 'label' => 'Solid'],
        ];

        foreach ($weeks as $w) {
            WorkforcePerformanceScore::updateOrCreate(
                ['user_id' => $operador->id, 'period_type' => 'weekly', 'period_start' => $w['start']],
                [
                    'sede_id'                => $sede->id,
                    'period_end'             => Carbon::parse($w['start'])->endOfWeek()->toDateString(),
                    'productivity_score'     => (int) ($w['score'] * 0.25),
                    'total_sales'            => rand(55, 95),
                    'sales_volume'           => round(rand(300, 600) + rand(0, 99) / 100, 2),
                    'session_count'          => 6,
                    'quality_score'          => (int) ($w['score'] * 0.30),
                    'avg_cash_difference_pct'=> round(rand(0, 100) / 100, 4),
                    'closings_count'         => 6,
                    'exact_closings'         => rand(4, 6),
                    'discipline_score'       => (int) ($w['score'] * 0.25),
                    'late_deposits'          => rand(0, 2),
                    'total_deposits'         => rand(4, 8),
                    'unclosed_sessions'      => 0,
                    'consistency_score'      => (int) ($w['score'] * 0.20),
                    'score_variance'         => round(rand(100, 500) / 100, 4),
                    'total_score'            => $w['score'],
                    'performance_label'      => $w['label'],
                    'computed_at'            => now(),
                ]
            );
        }
    }
}
