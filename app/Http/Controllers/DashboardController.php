<?php

namespace App\Http\Controllers;

use App\Models\CashClosing;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Sede;
use App\Models\StockAlert;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isBoss()) {
            return $this->bossDashboard();
        }

        return $this->operatorDashboard();
    }

    private function bossDashboard()
    {
        $today = now()->toDateString();

        $totalSalesToday = Sale::whereDate('fecha_venta', $today)->sum('total_sistema');
        $transactionCount = Sale::whereDate('fecha_venta', $today)->count();
        $pendingClosings = CashClosing::where('estado', 'pendiente')->count();
        $alertCount = StockAlert::where('alerta_activa', true)->count();

        $sedes = Sede::where('activa', true)->get();
        $topProducts = Product::whereHas('saleItems', function ($q) {
            $q->whereDate('created_at', now()->subDays(30));
        })
            ->with('saleItems')
            ->get()
            ->sortByDesc(function ($product) {
                return $product->saleItems->sum('cantidad');
            })
            ->take(10);

        $pendingCashClosings = CashClosing::where('estado', 'pendiente')
            ->with('sede', 'user')
            ->latest('fecha_cierre')
            ->get();

        $stockAlerts = StockAlert::where('alerta_activa', true)
            ->with('product', 'sede')
            ->latest('fecha_alerta')
            ->take(10)
            ->get();

        return view('admin.dashboard', [
            'totalSalesToday' => $totalSalesToday,
            'transactionCount' => $transactionCount,
            'pendingClosings' => $pendingClosings,
            'alertCount' => $alertCount,
            'sedes' => $sedes,
            'topProducts' => $topProducts,
            'pendingCashClosings' => $pendingCashClosings,
            'stockAlerts' => $stockAlerts,
        ]);
    }

    private function operatorDashboard()
    {
        $sede = Auth::user()->sede;
        $today = now()->toDateString();

        $lowStockProducts = Inventory::where('sede_id', $sede->id)
            ->whereRaw('cantidad_stock < ?', [DB::raw('(SELECT stock_minimo FROM products WHERE id = inventories.product_id)')])
            ->with('product')
            ->get();

        $todaysSales = Sale::where('sede_id', $sede->id)
            ->whereDate('fecha_venta', $today)
            ->with('items.product')
            ->get();

        $lastClosing = CashClosing::where('sede_id', $sede->id)
            ->latest('fecha_cierre')
            ->first();

        return view('operator.dashboard', [
            'sede' => $sede,
            'lowStockProducts' => $lowStockProducts,
            'todaysSales' => $todaysSales,
            'lastClosing' => $lastClosing,
        ]);
    }
}
