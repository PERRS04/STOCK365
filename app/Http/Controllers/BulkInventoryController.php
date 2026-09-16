<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sede;
use App\Services\ActivityLogger;
use App\Services\InventoryStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BulkInventoryController extends Controller
{
    public function __construct(private InventoryStockService $stockService) {}

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
            'sede_id'      => 'required|exists:sedes,id',
            'quantities'   => 'required|array',
            'quantities.*' => 'nullable|integer|min:0',
            'motivo'       => 'nullable|string|max:255',
        ]);

        $sede    = Sede::findOrFail($validated['sede_id']);
        $motivo  = ($validated['motivo'] ?? null) ?: 'Carga masiva de inventario inicial';
        $changed = 0;

        // Sort ASC by product_id for deterministic lock acquisition (deadlock prevention)
        $quantities = $validated['quantities'];
        ksort($quantities);

        DB::transaction(function () use ($quantities, $sede, $motivo, &$changed) {
            foreach ($quantities as $productId => $newQty) {
                if ($newQty === null || $newQty === '') continue;

                $newQty     = (int) $newQty;
                $currentQty = Inventory::where('product_id', $productId)
                    ->where('sede_id', $sede->id)
                    ->value('cantidad_stock') ?? 0;

                if ($currentQty === $newQty) continue;

                $this->stockService->setStockAbsolute(
                    productId:      (int) $productId,
                    targetCantidad: $newQty,
                    sedeId:         $sede->id,
                    almacenId:      null,
                    costoUnitario:  null,
                    userId:         auth()->id(),
                    motivo:         $motivo,
                );

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
