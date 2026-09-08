<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InventoryNotFoundException;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sede;
use App\Models\StockAlert;
use App\Services\ActivityLogger;
use App\Services\InventoryStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function __construct(private InventoryStockService $stockService) {}

    public function index()
    {
        abort_unless(auth()->user()->can('inventory.view'), 403);

        $sedes    = Sede::where('activa', true)->get();
        $products = Product::where('activo', true)->orderBy('nombre')->get();

        return view('admin.inventory.index', compact('sedes', 'products'));
    }

    public function movements()
    {
        abort_unless(auth()->user()->can('inventory.view'), 403);

        $movements = InventoryMovement::with('product', 'sede', 'user')
            ->latest('fecha_movimiento')
            ->paginate(30);

        return view('admin.inventory.movements', ['movements' => $movements]);
    }

    public function adjustStock(Request $request)
    {
        abort_unless(auth()->user()->can('inventory.adjust'), 403);

        $validated = $request->validate([
            'product_id'    => 'required|exists:products,id',
            'sede_id'       => 'required|exists:sedes,id',
            'cantidad'      => 'required|integer|not_in:0',
            'motivo'        => 'required|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        $tipo     = $validated['cantidad'] > 0 ? 'entrada' : 'salida';
        $cantidad = abs($validated['cantidad']);

        try {
            DB::transaction(function () use ($validated, $tipo, $cantidad) {
                $inventory = $tipo === 'entrada'
                    ? $this->stockService->entrada(
                        $validated['product_id'],
                        $cantidad,
                        $validated['sede_id'],
                        null,
                        null,
                        auth()->id(),
                        $validated['motivo']
                    )
                    : $this->stockService->salida(
                        $validated['product_id'],
                        $cantidad,
                        $validated['sede_id'],
                        null,
                        null,
                        auth()->id(),
                        $validated['motivo']
                    );

                // Derive stockAnterior from the confirmed post-update value — avoids stale snapshot.
                // The service atomically applied ±$cantidad via lockForUpdate, so the derivation is exact.
                $stockNuevo    = $inventory->cantidad_stock;
                $stockAnterior = $tipo === 'entrada' ? $stockNuevo - $cantidad : $stockNuevo + $cantidad;

                // Persist observaciones — not handled by the service.
                // Filter by all known attributes to avoid ambiguity under concurrent inserts.
                if (!empty($validated['observaciones'])) {
                    InventoryMovement::where([
                        'product_id' => $validated['product_id'],
                        'sede_id'    => $validated['sede_id'],
                        'almacen_id' => null,
                        'tipo'       => $tipo,
                        'cantidad'   => $cantidad,
                        'user_id'    => auth()->id(),
                    ])
                    ->latest('id')
                    ->first()
                    ?->update(['observaciones' => $validated['observaciones']]);
                }

                $product = Product::find($validated['product_id']);

                if ($stockNuevo < $product->stock_minimo) {
                    StockAlert::updateOrCreate(
                        ['product_id' => $product->id, 'sede_id' => $validated['sede_id']],
                        [
                            'stock_actual'  => $stockNuevo,
                            'stock_minimo'  => $product->stock_minimo,
                            'alerta_activa' => true,
                            'fecha_alerta'  => now(),
                        ]
                    );
                } else {
                    StockAlert::where('product_id', $product->id)
                        ->where('sede_id', $validated['sede_id'])
                        ->update(['alerta_activa' => false]);
                }

                ActivityLogger::log(
                    'inventory.adjust',
                    "Stock ajustado: {$product->nombre} | {$tipo} {$validated['cantidad']} unidades | Motivo: {$validated['motivo']}",
                    $inventory,
                    ['cantidad_stock' => $stockAnterior],
                    ['cantidad_stock' => $stockNuevo]
                );
            });
        } catch (InsufficientStockException $e) {
            return redirect()->back()->withErrors([
                'cantidad' => "Stock insuficiente. Disponible: {$e->disponible}. Requerido: {$e->requerido}.",
            ]);
        } catch (InventoryNotFoundException) {
            return redirect()->back()->withErrors([
                'cantidad' => 'No existe inventario para este producto en la sede seleccionada.',
            ]);
        }

        return redirect()->back()->with('success', 'Stock ajustado correctamente');
    }
}
