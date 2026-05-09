<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\CashClosing;
use Illuminate\Support\Facades\DB;

class ReportService
{
    public function getSalesByDateRange(string $startDate, string $endDate, ?int $sedeId = null)
    {
        $query = Sale::whereBetween('fecha_venta', [
            $startDate . ' 00:00:00',
            $endDate . ' 23:59:59'
        ]);

        if ($sedeId) {
            $query->where('sede_id', $sedeId);
        }

        return $query->with('sede', 'items.product')
            ->latest('fecha_venta')
            ->get();
    }

    public function getProfitabilityReport(string $startDate, string $endDate, ?int $sedeId = null)
    {
        $query = SaleItem::query()
            ->join('products', 'sale_items.product_id', '=', 'products.id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereBetween('sales.fecha_venta', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ])
            ->select(
                'products.id',
                'products.nombre',
                'products.marca',
                DB::raw('SUM(sale_items.cantidad) as total_vendido'),
                DB::raw('SUM(sale_items.subtotal) as total_ingresos'),
                DB::raw('SUM(sale_items.cantidad * products.precio_compra) as total_costo'),
                DB::raw('SUM(sale_items.subtotal - (sale_items.cantidad * products.precio_compra)) as utilidad_bruta')
            );

        if ($sedeId) {
            $query->where('sales.sede_id', $sedeId);
        }

        return $query->groupBy('products.id', 'products.nombre', 'products.marca')
            ->orderByDesc('utilidad_bruta')
            ->get();
    }
}
