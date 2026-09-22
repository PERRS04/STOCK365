<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
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
        $almacenes = Almacen::where('activo', true)->orderBy('nombre')->get();
        $products = Product::where('activo', true)->orderBy('nombre')->get();

        // Determine location type: sede (default) or almacen
        $locType   = $request->get('loc_type', 'sede');
        $sedeId    = $locType === 'sede'    ? $request->get('sede_id',    $sedes->first()?->id)    : null;
        $almacenId = $locType === 'almacen' ? $request->get('almacen_id', $almacenes->first()?->id) : null;

        $sede    = $sedeId    ? $sedes->find($sedeId)        : null;
        $almacen = $almacenId ? $almacenes->find($almacenId) : null;

        $currentStock = collect();

        if ($locType === 'sede' && $sede) {
            $currentStock = Inventory::where('sede_id', $sedeId)->whereNull('almacen_id')->pluck('cantidad_stock', 'product_id');
        } elseif ($locType === 'almacen' && $almacen) {
            $currentStock = Inventory::whereNull('sede_id')->where('almacen_id', $almacenId)->pluck('cantidad_stock', 'product_id');
        }

        return view('admin.inventory.bulk-load', compact(
            'sedes',
            'almacenes',
            'products',
            'sede',
            'almacen',
            'sedeId',
            'almacenId',
            'locType',
            'currentStock'
        ));
    }

    public function save(Request $request)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        $validated = $request->validate([
            'loc_type'     => 'required|in:sede,almacen',
            'sede_id'      => 'nullable|exists:sedes,id',
            'almacen_id'   => 'nullable|exists:almacenes,id',
            'quantities'   => 'required|array',
            'quantities.*' => 'nullable|integer|min:0',
            'motivo'       => 'nullable|string|max:255',
        ]);

        $locType   = $validated['loc_type'];
        $sedeId    = $locType === 'sede'    ? ($validated['sede_id']    ?? null) : null;
        $almacenId = $locType === 'almacen' ? ($validated['almacen_id'] ?? null) : null;

        // XOR: exactly one location
        if ($locType === 'sede' && ! $sedeId) {
            return back()->withInput()->withErrors(['sede_id' => 'Selecciona una sede.']);
        }
        if ($locType === 'almacen' && ! $almacenId) {
            return back()->withInput()->withErrors(['almacen_id' => 'Selecciona un depósito.']);
        }

        $sede    = $sedeId    ? Sede::findOrFail($sedeId)        : null;
        $almacen = $almacenId ? Almacen::findOrFail($almacenId)  : null;
        $label   = $sede?->nombre ?? $almacen?->nombre ?? '—';
        $motivo  = ($validated['motivo'] ?? null) ?: 'Carga masiva de inventario inicial';
        $changed   = 0;
        $oldValues = [];
        $newValues = [];

        // Sort ASC by product_id for deterministic lock acquisition (deadlock prevention)
        $quantities = $validated['quantities'];
        ksort($quantities);

        $productNames = Product::whereIn('id', array_keys($quantities))->pluck('nombre', 'id');

        DB::transaction(function () use (
            $quantities,
            $sedeId,
            $almacenId,
            $motivo,
            $productNames,
            &$changed,
            &$oldValues,
            &$newValues
        ) {
            foreach ($quantities as $productId => $newQty) {
                if ($newQty === null || $newQty === '') {
                    continue;
                }

                $newQty = (int) $newQty;

                $query = Inventory::where('product_id', $productId);
                if ($sedeId) {
                    $query->where('sede_id', $sedeId)->whereNull('almacen_id');
                } else {
                    $query->whereNull('sede_id')->where('almacen_id', $almacenId);
                }
                $currentQty = $query->value('cantidad_stock') ?? 0;

                if ($currentQty === $newQty) {
                    continue;
                }

                $productLabel = ($productNames[$productId] ?? "Producto #{$productId}")
                    . " (#{$productId})";

                $oldValues[$productLabel] = $currentQty;
                $newValues[$productLabel] = $newQty;

                $this->stockService->setStockAbsolute(
                    productId:      (int) $productId,
                    targetCantidad: $newQty,
                    sedeId:         $sedeId,
                    almacenId:      $almacenId,
                    costoUnitario:  null,
                    userId:         auth()->id(),
                    motivo:         $motivo,
                );

                $changed++;
            }
        });

        ActivityLogger::log(
            'inventory.bulk_load',
            "Carga masiva: {$label} — {$changed} productos actualizados. Motivo: {$motivo}",
            null,
            $oldValues,
            $newValues,
            $sedeId,
            $almacenId,
        );

        $params = ['loc_type' => $locType];
        if ($sedeId)    $params['sede_id']    = $sedeId;
        if ($almacenId) $params['almacen_id'] = $almacenId;

        return redirect()
            ->route('inventory.bulk-load', $params)
            ->with('success', "Inventario actualizado: {$changed} productos modificados en {$label}.");
    }
}
