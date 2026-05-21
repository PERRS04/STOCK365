<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sede;
use Illuminate\Http\Request;

class KardexController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('inventory.view'), 403);

        $products = Product::where('activo', true)->orderBy('nombre')->get();
        $sedes    = Sede::orderBy('nombre')->get();

        $movements  = collect();
        $product    = null;
        $sede       = null;
        $saldoInicial = 0;

        if ($request->filled('product_id')) {
            $product = Product::find($request->product_id);

            $query = InventoryMovement::with('sede', 'user')
                ->where('product_id', $request->product_id);

            if ($request->filled('sede_id')) {
                $query->where('sede_id', $request->sede_id);
                $sede = Sede::find($request->sede_id);
            }

            if ($request->filled('desde')) {
                // Calculate saldo before the start date
                $saldoInicial = $this->calculateSaldoAntes($request->product_id, $request->sede_id ?? null, $request->desde);
                $query->whereDate('fecha_movimiento', '>=', $request->desde);
            }

            if ($request->filled('hasta')) {
                $query->whereDate('fecha_movimiento', '<=', $request->hasta);
            }

            $movements = $query->orderBy('fecha_movimiento')->orderBy('id')->get();

            // Build running balance
            $saldo = $saldoInicial;
            $movements = $movements->map(function ($mov) use (&$saldo) {
                $esEntrada = in_array($mov->tipo, ['entrada', 'ajuste']);
                if ($esEntrada) {
                    $saldo += $mov->cantidad;
                } else {
                    $saldo -= $mov->cantidad;
                }
                $mov->saldo_acumulado = $saldo;
                return $mov;
            });
        }

        return view('admin.kardex.index', compact(
            'products', 'sedes', 'movements', 'product', 'sede', 'saldoInicial'
        ));
    }

    private function calculateSaldoAntes(?int $productId, ?int $sedeId, string $fecha): int
    {
        $query = InventoryMovement::where('product_id', $productId)
            ->whereDate('fecha_movimiento', '<', $fecha);

        if ($sedeId) {
            $query->where('sede_id', $sedeId);
        }

        $entradas = (clone $query)->whereIn('tipo', ['entrada', 'ajuste'])->sum('cantidad');
        $salidas  = (clone $query)->whereIn('tipo', ['salida', 'pérdida', 'transferencia'])->sum('cantidad');

        return max(0, $entradas - $salidas);
    }
}
