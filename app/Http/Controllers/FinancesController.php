<?php

namespace App\Http\Controllers;

use App\Models\InventoryReceipt;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Sede;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancesController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('reports.view'), 403);

        $mes  = (int) $request->get('mes',  now()->month);
        $anio = (int) $request->get('anio', now()->year);

        // ── Core KPIs ─────────────────────────────────────────────────────────
        $ventasMes = Sale::whereMonth('fecha_venta', $mes)
            ->whereYear('fecha_venta', $anio)
            ->where('estado', 'completada')
            ->sum('total_sistema');

        $comprasMes = InventoryReceipt::where('estado', 'aprobado')
            ->whereMonth('created_at', $mes)
            ->whereYear('created_at', $anio)
            ->sum('monto_pagado');

        // Real cost from sale items
        $costoVentasMes = SaleItem::whereHas('sale', fn($q) =>
            $q->whereMonth('fecha_venta', $mes)
              ->whereYear('fecha_venta', $anio)
              ->where('estado', 'completada')
        )->selectRaw('SUM(cantidad * costo_unitario) as total')->value('total') ?? 0;

        $utilidadBruta = $ventasMes - $costoVentasMes;
        $margenPct     = $ventasMes > 0 ? round(($utilidadBruta / $ventasMes) * 100, 1) : 0;

        // ── Monthly trend (last 6 months) ─────────────────────────────────────
        $trend = collect(range(5, 0))->map(function ($mesesAtras) {
            $fecha   = now()->subMonths($mesesAtras);
            $m = $fecha->month; $y = $fecha->year;

            $ventas = Sale::whereMonth('fecha_venta', $m)->whereYear('fecha_venta', $y)
                ->where('estado', 'completada')->sum('total_sistema');

            $compras = InventoryReceipt::where('estado', 'aprobado')
                ->whereMonth('created_at', $m)->whereYear('created_at', $y)
                ->sum('monto_pagado');

            $costoVentas = SaleItem::whereHas('sale', fn($q) =>
                $q->whereMonth('fecha_venta', $m)->whereYear('fecha_venta', $y)->where('estado', 'completada')
            )->selectRaw('SUM(cantidad * costo_unitario) as total')->value('total') ?? 0;

            return [
                'label'    => $fecha->format('M Y'),
                'ventas'   => round($ventas, 2),
                'compras'  => round($compras, 2),
                'utilidad' => round($ventas - $costoVentas, 2),
            ];
        });

        // ── Sales by sede ─────────────────────────────────────────────────────
        $ventasPorSede = Sale::whereMonth('fecha_venta', $mes)
            ->whereYear('fecha_venta', $anio)
            ->where('estado', 'completada')
            ->select('sede_id', DB::raw('SUM(total_sistema) as total, COUNT(*) as count'))
            ->groupBy('sede_id')
            ->with('sede')
            ->get();

        // ── Purchases by provider ─────────────────────────────────────────────
        $comprasPorProveedor = InventoryReceipt::where('estado', 'aprobado')
            ->whereMonth('created_at', $mes)
            ->whereYear('created_at', $anio)
            ->select('provider_id', DB::raw('SUM(monto_pagado) as total, COUNT(*) as count'))
            ->groupBy('provider_id')
            ->with('provider')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        // ── Daily comparison (last 30 days) ───────────────────────────────────
        $dailyVentas = Sale::where('fecha_venta', '>=', now()->subDays(29))
            ->where('estado', 'completada')
            ->selectRaw('DATE(fecha_venta) as dia, SUM(total_sistema) as total')
            ->groupBy('dia')
            ->orderBy('dia')
            ->pluck('total', 'dia');

        $sedes = Sede::orderBy('nombre')->get();
        $meses = collect(range(1, 12))->mapWithKeys(fn($m) =>
            [$m => now()->setMonth($m)->format('F')]
        );
        $anios = range(now()->year - 2, now()->year);

        return view('admin.finances.index', compact(
            'ventasMes', 'comprasMes', 'costoVentasMes', 'utilidadBruta', 'margenPct',
            'trend', 'ventasPorSede', 'comprasPorProveedor', 'dailyVentas',
            'sedes', 'mes', 'anio', 'meses', 'anios'
        ));
    }
}
