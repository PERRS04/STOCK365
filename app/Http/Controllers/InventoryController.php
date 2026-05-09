<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sede;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index()
    {
        $this->authorize('boss');

        $sedes = Sede::where('activa', true)->get();
        $sedeId = request('sede_id', $sedes->first()?->id);

        $inventories = Inventory::where('sede_id', $sedeId)
            ->with('product')
            ->paginate(20);

        return view('admin.inventory.index', [
            'inventories' => $inventories,
            'sedes' => $sedes,
            'selectedSede' => $sedeId,
        ]);
    }

    public function movements()
    {
        $this->authorize('boss');

        $movements = InventoryMovement::with('product', 'sede', 'user')
            ->latest('fecha_movimiento')
            ->paginate(30);

        return view('admin.inventory.movements', ['movements' => $movements]);
    }

    public function adjustStock(Request $request)
    {
        $this->authorize('boss');

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'sede_id' => 'required|exists:sedes,id',
            'cantidad' => 'required|integer',
            'motivo' => 'required|string',
            'observaciones' => 'nullable|string',
        ]);

        $inventory = Inventory::firstOrCreate(
            ['product_id' => $validated['product_id'], 'sede_id' => $validated['sede_id']],
            ['cantidad_stock' => 0]
        );

        $tipo = $validated['cantidad'] > 0 ? 'entrada' : 'salida';

        InventoryMovement::create([
            'product_id' => $validated['product_id'],
            'sede_id' => $validated['sede_id'],
            'tipo' => $tipo,
            'cantidad' => abs($validated['cantidad']),
            'motivo' => $validated['motivo'],
            'user_id' => auth()->id(),
            'observaciones' => $validated['observaciones'] ?? null,
            'fecha_movimiento' => now(),
        ]);

        $inventory->update([
            'cantidad_stock' => $inventory->cantidad_stock + $validated['cantidad'],
            'ultima_actualizacion' => now(),
        ]);

        return redirect()->back()->with('success', 'Stock ajustado correctamente');
    }
}
