<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\CashClosing;
use App\Models\Inventory;
use App\Models\Sede;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesExport;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $this->authorize('boss');

        $query = Sale::query();

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('fecha_venta', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        if ($request->sede_id) {
            $query->where('sede_id', $request->sede_id);
        }

        $sales = $query->with('sede', 'items')
            ->latest('fecha_venta')
            ->paginate(30);

        $sedes = Sede::where('activa', true)->get();

        return view('admin.reports.sales', [
            'sales' => $sales,
            'sedes' => $sedes,
        ]);
    }

    public function profitability(Request $request)
    {
        $this->authorize('boss');

        $query = SaleItem::query()
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->select(
                'sale_items.product_id',
                'products.nombre',
                'products.marca',
                DB::raw('SUM(sale_items.cantidad) as total_cantidad'),
                DB::raw('SUM(sale_items.subtotal) as total_venta'),
                DB::raw('SUM(sale_items.cantidad * products.precio_compra) as total_costo'),
                DB::raw('SUM(sale_items.subtotal - (sale_items.cantidad * products.precio_compra)) as utilidad_bruta')
            );

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('sales.fecha_venta', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        if ($request->sede_id) {
            $query->where('sales.sede_id', $request->sede_id);
        }

        $profitability = $query->groupBy('products.id', 'products.nombre', 'products.marca')
            ->get();

        $sedes = Sede::where('activa', true)->get();

        return view('admin.reports.profitability', [
            'profitability' => $profitability,
            'sedes' => $sedes,
        ]);
    }

    public function topProducts(Request $request)
    {
        $this->authorize('boss');

        $query = SaleItem::query()
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->select(
                'products.id',
                'products.nombre',
                'products.marca',
                'products.tamaño',
                DB::raw('SUM(sale_items.cantidad) as total_vendido'),
                DB::raw('SUM(sale_items.subtotal) as total_ingresos')
            );

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('sales.fecha_venta', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        $topProducts = $query->groupBy('products.id', 'products.nombre', 'products.marca', 'products.tamaño')
            ->orderByDesc('total_vendido')
            ->take(20)
            ->get();

        return view('admin.reports.top-products', ['topProducts' => $topProducts]);
    }

    public function comparison(Request $request)
    {
        $this->authorize('boss');

        $sedes = Sede::where('activa', true)
            ->withCount('sales')
            ->with('sales')
            ->get()
            ->map(function ($sede) {
                return [
                    'nombre' => $sede->nombre,
                    'total_ventas' => $sede->sales->sum('total_sistema'),
                    'transacciones' => $sede->sales_count,
                    'promedio_venta' => $sede->sales->avg('total_sistema'),
                ];
            });

        return view('admin.reports.comparison', ['sedes' => $sedes]);
    }

    public function exportSales()
    {
        $this->authorize('boss');
        return Excel::download(new SalesExport, 'ventas.xlsx');
    }
}
