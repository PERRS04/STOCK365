<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Sede;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesExport;

class ReportController extends Controller
{
    public function sales()
    {
        abort_unless(auth()->user()->can('reports.view'), 403);

        return view('admin.reports.sales');
    }

    public function profitability(Request $request)
    {
        abort_unless(auth()->user()->can('reports.view'), 403);

        $query = SaleItem::query()
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.estado', 'completada')
            ->select(
                'sale_items.product_id',
                'products.nombre',
                'products.marca',
                DB::raw('SUM(sale_items.cantidad) as total_cantidad'),
                DB::raw('SUM(sale_items.subtotal) as total_venta'),
                DB::raw('SUM(sale_items.cantidad * sale_items.costo_unitario) as total_costo'),
                DB::raw('SUM(sale_items.subtotal - (sale_items.cantidad * sale_items.costo_unitario)) as utilidad_bruta')
            );

        if ($request->start_date && $request->end_date) {
            $query->whereBetween('sales.fecha_venta', [
                $request->start_date . ' 00:00:00',
                $request->end_date   . ' 23:59:59',
            ]);
        }

        if ($request->sede_id) {
            $query->where('sales.sede_id', $request->sede_id);
        }

        $profitability = $query
            ->groupBy('products.id', 'products.nombre', 'products.marca')
            ->get();

        $sedes = Sede::where('activa', true)->get();

        return view('admin.reports.profitability', compact('profitability', 'sedes'));
    }

    public function topProducts(Request $request)
    {
        abort_unless(auth()->user()->can('reports.view'), 403);

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
                $request->end_date   . ' 23:59:59',
            ]);
        }

        $topProducts = $query
            ->groupBy('products.id', 'products.nombre', 'products.marca', 'products.tamaño')
            ->orderByDesc('total_vendido')
            ->take(20)
            ->get();

        return view('admin.reports.top-products', compact('topProducts'));
    }

    public function comparison(Request $request)
    {
        abort_unless(auth()->user()->can('reports.view'), 403);

        $mes  = (int) $request->get('mes', now()->month);
        $anio = (int) $request->get('anio', now()->year);

        $rawData = Sale::selectRaw('sede_id, COUNT(*) as transacciones, SUM(total_sistema) as total_ventas, AVG(total_sistema) as promedio_venta')
            ->where('estado', 'completada')
            ->whereMonth('fecha_venta', $mes)
            ->whereYear('fecha_venta', $anio)
            ->groupBy('sede_id')
            ->get()
            ->keyBy('sede_id');

        $sedes = Sede::where('activa', true)->orderBy('nombre')->get()->map(fn($sede) => [
            'nombre'         => $sede->nombre,
            'total_ventas'   => (float) ($rawData[$sede->id]?->total_ventas ?? 0),
            'transacciones'  => (int)   ($rawData[$sede->id]?->transacciones ?? 0),
            'promedio_venta' => (float) ($rawData[$sede->id]?->promedio_venta ?? 0),
        ]);

        $meses = collect(range(1, 12))->mapWithKeys(fn($m) => [$m => now()->setMonth($m)->format('F')]);
        $anios = range(now()->year - 2, now()->year);

        return view('admin.reports.comparison', compact('sedes', 'mes', 'anio', 'meses', 'anios'));
    }

    public function exportSales()
    {
        abort_unless(auth()->user()->can('reports.export'), 403);

        return Excel::download(new SalesExport, 'ventas.xlsx');
    }
}
