<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sede;
use App\Models\StockAlert;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
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

        $inventory = Inventory::firstOrCreate(
            ['product_id' => $validated['product_id'], 'sede_id' => $validated['sede_id']],
            ['cantidad_stock' => 0]
        );

        $stockAnterior = $inventory->cantidad_stock;
        $tipo = $validated['cantidad'] > 0 ? 'entrada' : 'salida';

        InventoryMovement::create([
            'product_id'       => $validated['product_id'],
            'sede_id'          => $validated['sede_id'],
            'tipo'             => $tipo,
            'cantidad'         => abs($validated['cantidad']),
            'motivo'           => $validated['motivo'],
            'user_id'          => auth()->id(),
            'observaciones'    => $validated['observaciones'] ?? null,
            'fecha_movimiento' => now(),
        ]);

        $stockNuevo = $stockAnterior + $validated['cantidad'];
        $inventory->update([
            'cantidad_stock'       => $stockNuevo,
            'ultima_actualizacion' => now(),
        ]);

        $product = Product::find($validated['product_id']);

        // Auto-manage stock alert for this product/sede
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

        return redirect()->back()->with('success', 'Stock ajustado correctamente');
    }
}
