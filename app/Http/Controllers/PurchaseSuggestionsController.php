<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sede;
use Illuminate\Http\Request;

class PurchaseSuggestionsController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('inventory.view'), 403);

        $sedes    = Sede::orderBy('nombre')->get();
        $sedeId   = $request->get('sede_id');

        $inventoryQuery = Inventory::with('product', 'sede')
            ->whereHas('product', fn($q) => $q->where('activo', true));

        if ($sedeId) {
            $inventoryQuery->where('sede_id', $sedeId);
        }

        $inventories = $inventoryQuery->get();

        // Pre-aggregate sales last 30 days keyed by "product_id:sede_id"
        $since30 = now()->subDays(30);
        $salesMap = InventoryMovement::selectRaw('product_id, sede_id, SUM(cantidad) as total')
            ->where('tipo', 'salida')
            ->where('fecha_movimiento', '>=', $since30)
            ->groupBy('product_id', 'sede_id')
            ->get()
            ->keyBy(fn($r) => $r->product_id . ':' . $r->sede_id);

        $suggestions = $inventories->map(function ($inv) use ($salesMap) {
            $product = $inv->product;

            $salesLast30 = (int) ($salesMap[$product->id . ':' . $inv->sede_id]?->total ?? 0);

            $avgDiario = $salesLast30 / 30;
            $diasRestantes = $avgDiario > 0 ? round($inv->cantidad_stock / $avgDiario, 1) : null;

            $stockMinimo     = $product->stock_minimo ?? 0;
            $stockRecomendado = max($inv->stock_recomendado, $stockMinimo * 2);
            $suggestedQty    = max(0, $stockRecomendado - $inv->cantidad_stock);

            if ($suggestedQty <= 0 && $inv->cantidad_stock > $stockRecomendado) {
                return null;
            }

            $urgencia = 'normal';
            if ($inv->cantidad_stock <= $stockMinimo) {
                $urgencia = 'critico';
            } elseif ($inv->cantidad_stock <= $stockRecomendado) {
                $urgencia = 'alerta';
            }

            return [
                'product'          => $product,
                'sede'             => $inv->sede,
                'stock_actual'     => $inv->cantidad_stock,
                'stock_minimo'     => $stockMinimo,
                'stock_recomendado'=> $stockRecomendado,
                'avg_diario'       => round($avgDiario, 1),
                'dias_restantes'   => $diasRestantes,
                'suggested_qty'    => $suggestedQty,
                'urgencia'         => $urgencia,
            ];
        })->filter()->sortByDesc(fn($s) => match($s['urgencia']) {
            'critico' => 3, 'alerta' => 2, default => 1
        })->values();

        $criticalCount = $suggestions->where('urgencia', 'critico')->count();
        $alertCount    = $suggestions->where('urgencia', 'alerta')->count();

        return view('admin.purchase-suggestions.index', compact(
            'suggestions', 'sedes', 'sedeId', 'criticalCount', 'alertCount'
        ));
    }
}
