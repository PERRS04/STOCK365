<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sede;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BulkInventoryController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        $sedes    = Sede::where('activa', true)->orderBy('nombre')->get();
        $products = Product::where('activo', true)->orderBy('nombre')->get();
        $sedeId   = $request->get('sede_id', $sedes->first()?->id);
        $sede     = $sedes->find($sedeId);

        // Current stock keyed by product_id
        $currentStock = $sede
            ? Inventory::where('sede_id', $sedeId)->pluck('cantidad_stock', 'product_id')
            : collect();

        return view('admin.inventory.bulk-load', compact(
            'sedes', 'products', 'sede', 'sedeId', 'currentStock'
        ));
    }

    public function save(Request $request)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        $validated = $request->validate([
            'sede_id'             => 'required|exists:sedes,id',
            'quantities'          => 'required|array',
            'quantities.*'        => 'nullable|integer|min:0',
            'motivo'              => 'nullable|string|max:255',
        ]);

        $sede   = Sede::findOrFail($validated['sede_id']);
        $motivo = $validated['motivo'] ?: 'Carga masiva de inventario inicial';
        $changed = 0;

        DB::transaction(function () use ($validated, $sede, $motivo, &$changed) {
            foreach ($validated['quantities'] as $productId => $newQty) {
                if ($newQty === null || $newQty === '') continue;

                $newQty = (int) $newQty;

                $inventory = Inventory::firstOrCreate(
                    ['product_id' => $productId, 'sede_id' => $sede->id],
                    ['cantidad_stock' => 0, 'stock_recomendado' => 0]
                );

                $oldQty = $inventory->cantidad_stock;
                $diff   = $newQty - $oldQty;

                if ($diff === 0) continue;

                $inventory->update([
                    'cantidad_stock'       => $newQty,
                    'ultima_actualizacion' => now(),
                ]);

                InventoryMovement::create([
                    'product_id'       => $productId,
                    'sede_id'          => $sede->id,
                    'tipo'             => $diff > 0 ? 'entrada' : 'salida',
                    'cantidad'         => abs($diff),
                    'motivo'           => $motivo,
                    'user_id'          => auth()->id(),
                    'fecha_movimiento' => now(),
                ]);

                $changed++;
            }
        });

        ActivityLogger::log(
            'inventory.bulk_load',
            "Carga masiva: {$sede->nombre} — {$changed} productos actualizados. Motivo: {$motivo}",
        );

        return redirect()
            ->route('inventory.bulk-load', ['sede_id' => $validated['sede_id']])
            ->with('success', "Inventario actualizado: {$changed} productos modificados en {$sede->nombre}.");
    }
}
