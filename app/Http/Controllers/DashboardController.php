<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CashClosing;
use App\Models\CashSession;
use App\Models\InventoryReceipt;
use App\Models\Sale;
use App\Models\Sede;
use App\Models\StockAlert;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        return Auth::user()->isAdminLevel()
            ? $this->bossDashboard()
            : $this->operatorDashboard();
    }

    private function bossDashboard()
    {
        $today  = now()->toDateString();
        $isBoss = Auth::user()->isBoss();

        // ── KPIs ──────────────────────────────────────────────────────────────
        $totalSalesToday = Sale::whereDate('fecha_venta', $today)->where('estado', 'completada')->sum('total_sistema');
        $transactionCount = Sale::whereDate('fecha_venta', $today)->where('estado', 'completada')->count();
        $avgTicket = $transactionCount > 0 ? $totalSalesToday / $transactionCount : 0;

        $totalSalesMonth = Sale::whereMonth('fecha_venta', now()->month)
            ->whereYear('fecha_venta', now()->year)
            ->where('estado', 'completada')
            ->sum('total_sistema');

        $utilidadHoy = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereDate('sales.fecha_venta', $today)
            ->where('sales.estado', 'completada')
            ->sum(DB::raw('sale_items.cantidad * (products.precio_venta - products.precio_compra)'));

        $pendingClosings     = CashClosing::where('estado', 'pendiente')->count();
        $alertCount          = StockAlert::where('alerta_activa', true)->count();
        $activeSessionsCount = CashSession::whereIn('status', ['open', 'pending_closing'])->count();
        $pendingReceiptsCount = InventoryReceipt::where('estado', 'pendiente')->count();

        // ── 7-day chart ───────────────────────────────────────────────────────
        $rawSemana = Sale::selectRaw('DATE(fecha_venta) as fecha, SUM(total_sistema) as total')
            ->where('estado', 'completada')
            ->whereBetween('fecha_venta', [now()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->groupBy('fecha')
            ->pluck('total', 'fecha');

        $chartSemana = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $chartSemana[] = [
                'label' => $d->locale('es')->isoFormat('ddd D'),
                'total' => (float) ($rawSemana[$d->toDateString()] ?? 0),
            ];
        }

        // ── Sede stats ────────────────────────────────────────────────────────
        $rawPorSede = Sale::selectRaw('sede_id, SUM(total_sistema) as total')
            ->whereDate('fecha_venta', $today)->where('estado', 'completada')
            ->groupBy('sede_id')->pluck('total', 'sede_id');

        $rawTxPorSede = Sale::selectRaw('sede_id, COUNT(*) as total')
            ->whereDate('fecha_venta', $today)->where('estado', 'completada')
            ->groupBy('sede_id')->pluck('total', 'sede_id');

        $rawAlertasPorSede = StockAlert::selectRaw('sede_id, COUNT(*) as total')
            ->where('alerta_activa', true)
            ->groupBy('sede_id')->pluck('total', 'sede_id');

        $rawCierresPorSede = CashClosing::selectRaw('sede_id, COUNT(*) as total')
            ->where('estado', 'pendiente')
            ->groupBy('sede_id')->pluck('total', 'sede_id');

        $sedes = Sede::where('activa', true)->get();

        $sedeStats = $sedes->map(fn($sede) => [
            'id'            => $sede->id,
            'nombre'        => $sede->nombre,
            'ciudad'        => $sede->ciudad,
            'ventas_hoy'    => (float) ($rawPorSede[$sede->id] ?? 0),
            'transacciones' => (int)   ($rawTxPorSede[$sede->id] ?? 0),
            'alertas'       => (int)   ($rawAlertasPorSede[$sede->id] ?? 0),
            'cierres_pend'  => (int)   ($rawCierresPorSede[$sede->id] ?? 0),
        ]);

        // ── Top 5 products ────────────────────────────────────────────────────
        $topProducts = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.estado', 'completada')
            ->where('sales.fecha_venta', '>=', now()->subDays(30))
            ->select('products.nombre', 'products.marca', DB::raw('SUM(sale_items.cantidad) as unidades'), DB::raw('SUM(sale_items.subtotal) as ingresos'))
            ->groupBy('products.nombre', 'products.marca')
            ->orderByDesc('unidades')
            ->take(5)
            ->get();

        // ── Pending closings (compact) ────────────────────────────────────────
        $pendingCashClosings = CashClosing::where('estado', 'pendiente')
            ->with('sede', 'user')->latest()->take(4)->get();

        // ── Critical stock alerts ─────────────────────────────────────────────
        $stockAlerts = StockAlert::where('alerta_activa', true)
            ->with('product', 'sede')->orderByRaw('stock_actual ASC')->take(8)->get();

        // ── Activity feed (compact) ───────────────────────────────────────────
        $recentActivity = ActivityLog::with('sede')->latest()->take(7)->get();

        return view('admin.dashboard', compact(
            'totalSalesToday', 'totalSalesMonth', 'transactionCount', 'avgTicket',
            'utilidadHoy', 'pendingClosings', 'alertCount',
            'activeSessionsCount', 'pendingReceiptsCount',
            'sedes', 'sedeStats', 'chartSemana',
            'topProducts', 'pendingCashClosings',
            'stockAlerts', 'recentActivity',
            'isBoss', 'today'
        ));
    }

    private function operatorDashboard()
    {
        $user  = Auth::user();
        $sede  = $user->sede;
        $today = now()->toDateString();

        if (! $sede) {
            return view('operator.dashboard', [
                'sede'                => null,
                'lowStockProducts'    => collect(),
                'todaysSales'         => collect(),
                'lastClosing'         => null,
                'cashSession'         => null,
                'totalVentasHoy'      => 0,
                'ticketPromedio'      => 0,
                'productosVendidosHoy'=> 0,
                'pendingReceipts'     => 0,
            ]);
        }

        $lowStockProducts = \App\Models\Inventory::where('sede_id', $sede->id)
            ->whereRaw('cantidad_stock < (SELECT stock_minimo FROM products WHERE id = inventories.product_id)')
            ->with('product')->get();

        $todaysSales = Sale::where('sede_id', $sede->id)
            ->whereDate('fecha_venta', $today)->where('estado', 'completada')
            ->with('items')->latest('fecha_venta')->get();

        $totalVentasHoy       = $todaysSales->sum('total_sistema');
        $ticketPromedio       = $todaysSales->count() > 0 ? $totalVentasHoy / $todaysSales->count() : 0;
        $productosVendidosHoy = $todaysSales->sum(fn($s) => $s->items->sum('cantidad'));

        $lastClosing = CashClosing::where('sede_id', $sede->id)->latest('fecha_cierre')->first();
        $cashSession = CashSession::activeForUser($user->id, $sede->id);

        $pendingReceipts = InventoryReceipt::where('sede_id', $sede->id)
            ->where('user_id', $user->id)
            ->where('estado', 'pendiente')
            ->count();

        return view('operator.dashboard', [
            'sede'                => $sede,
            'lowStockProducts'    => $lowStockProducts,
            'todaysSales'         => $todaysSales,
            'lastClosing'         => $lastClosing,
            'cashSession'         => $cashSession,
            'totalVentasHoy'      => $totalVentasHoy,
            'ticketPromedio'      => $ticketPromedio,
            'productosVendidosHoy'=> $productosVendidosHoy,
            'pendingReceipts'     => $pendingReceipts,
        ]);
    }
}
