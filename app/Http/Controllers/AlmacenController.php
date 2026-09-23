<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\InventoryReceipt;
use App\Models\InventoryReceiptItem;
use App\Models\Product;
use App\Models\Provider;
use App\Services\ActivityLogger;
use App\Services\InventoryStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlmacenController extends Controller
{
    public function __construct(private InventoryStockService $stockService) {}

    public function index()
    {
        abort_unless(auth()->user()->isBoss(), 403);

        $almacenes = Almacen::orderBy('nombre')->paginate(20);

        return view('admin.almacenes.index', compact('almacenes'));
    }

    public function create()
    {
        abort_unless(auth()->user()->isBoss(), 403);

        return view('admin.almacenes.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        $validated = $request->validate([
            'nombre'      => 'required|string|max:255|unique:almacenes,nombre',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        Almacen::create($validated + ['activo' => true]);

        return redirect()->route('almacenes.index')
            ->with('success', 'Almacén creado exitosamente.');
    }

    public function show(Request $request, Almacen $almacen)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        $search      = $request->get('buscar', '');
        $stockFilter = $request->get('stock', 'todos');

        $query = Inventory::where('almacen_id', $almacen->id)
            ->whereNull('sede_id')
            ->with('product')
            ->whereHas('product', fn($q) => $q->where('activo', true));

        if ($stockFilter === 'con_stock') {
            $query->where('cantidad_stock', '>', 0);
        } elseif ($stockFilter === 'sin_stock') {
            $query->where('cantidad_stock', '<=', 0);
        }

        $inventories = $query->orderByDesc('cantidad_stock')->get();

        if ($search) {
            $lower = mb_strtolower($search);
            $inventories = $inventories->filter(
                fn($inv) => str_contains(mb_strtolower($inv->product?->nombre ?? ''), $lower)
                         || str_contains(mb_strtolower($inv->product?->sku ?? ''), $lower)
            );
        }

        // KPIs from full (unfiltered) almacen inventory
        $allInv                 = Inventory::where('almacen_id', $almacen->id)->whereNull('sede_id')->with('product')->get();
        $totalProductosConStock = $allInv->where('cantidad_stock', '>', 0)->count();
        $totalUnidades          = (int) $allInv->sum('cantidad_stock');
        $valorInventario        = $allInv->sum(fn($inv) => $inv->cantidad_stock * (float) ($inv->product?->precio_compra ?? 0));

        // Legacy aliases expected by AlmacenInventoryTest
        $totalProductos = $allInv->count();
        $totalStock     = $totalUnidades;
        $movimientosRecientes   = InventoryMovement::where('almacen_id', $almacen->id)
            ->where('fecha_movimiento', '>=', now()->subDays(30))
            ->count();

        // Latest movement timestamp per product (for stock table column)
        $lastMovements = InventoryMovement::where('almacen_id', $almacen->id)
            ->selectRaw('product_id, MAX(fecha_movimiento) as last_at')
            ->groupBy('product_id')
            ->pluck('last_at', 'product_id');

        // Most recent activity (for header badge)
        $lastActivity = InventoryMovement::where('almacen_id', $almacen->id)
            ->latest('fecha_movimiento')
            ->value('fecha_movimiento');

        $providers = Provider::where('activo', true)->orderBy('nombre')->get();
        $products  = Product::where('activo', true)->orderBy('nombre')->get();

        return view('admin.almacenes.show', compact(
            'almacen', 'inventories', 'search', 'stockFilter',
            'totalProductosConStock', 'totalUnidades', 'valorInventario',
            'movimientosRecientes', 'lastMovements', 'lastActivity',
            'providers', 'products',
            'totalProductos', 'totalStock',
        ));
    }

    public function movements(Request $request, Almacen $almacen)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        $query = InventoryMovement::where('almacen_id', $almacen->id)
            ->with('product', 'user');

        if ($request->filled('desde')) {
            $query->whereDate('fecha_movimiento', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->whereDate('fecha_movimiento', '<=', $request->hasta);
        }
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('tipo')) {
            $allowed = ['entrada', 'salida', 'ajuste', 'pérdida', 'transferencia'];
            if (in_array($request->tipo, $allowed)) {
                $query->where('tipo', $request->tipo);
            }
        }

        $movements = $query->latest('fecha_movimiento')->paginate(30)->withQueryString();

        $productIds = InventoryMovement::where('almacen_id', $almacen->id)->distinct()->pluck('product_id');
        $products   = Product::whereIn('id', $productIds)->orderBy('nombre')->get();

        return view('admin.almacenes.movements', compact('almacen', 'movements', 'products'));
    }

    public function edit(Almacen $almacen)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        return view('admin.almacenes.edit', compact('almacen'));
    }

    public function update(Request $request, Almacen $almacen)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        $validated = $request->validate([
            'nombre'      => 'required|string|max:255|unique:almacenes,nombre,' . $almacen->id,
            'descripcion' => 'nullable|string|max:1000',
        ]);

        $validated['activo'] = $request->boolean('activo');

        $almacen->update($validated);

        return redirect()->route('almacenes.index')
            ->with('success', 'Almacén actualizado exitosamente.');
    }

    // ── ENTRADA DE MERCANCÍA ──────────────────────────────────────────────────

    public function createEntry(Almacen $almacen)
    {
        abort_unless(auth()->user()->isBoss(), 403);
        abort_unless($almacen->activo, 403);

        $providers = Provider::where('activo', true)->orderBy('nombre')->get();
        $products  = Product::where('activo', true)->orderBy('nombre')->get();

        return view('admin.almacenes.entry', compact('almacen', 'providers', 'products'));
    }

    public function storeEntry(Request $request, Almacen $almacen)
    {
        abort_unless(auth()->user()->isBoss(), 403);
        abort_unless($almacen->activo, 403);

        $validated = $request->validate([
            'provider_id'            => 'nullable|exists:providers,id',
            'referencia'             => 'nullable|string|max:255',
            'observaciones'          => 'nullable|string|max:1000',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.cantidad'       => 'required|integer|min:1',
            'items.*.costo_unitario' => 'required|numeric|min:0',
        ]);

        $productIds = collect($validated['items'])->pluck('product_id');
        if ($productIds->unique()->count() !== $productIds->count()) {
            return back()->withInput()->with('error', 'No puedes incluir el mismo producto más de una vez.');
        }

        $provider     = ($validated['provider_id'] ?? null) ? Provider::find($validated['provider_id']) : null;
        $supplierName = $provider?->nombre ?? ($validated['referencia'] ?? 'Entrada directa');

        try {
            DB::transaction(function () use ($validated, $almacen, $supplierName, $provider) {
                $receipt = InventoryReceipt::create([
                    'sede_id'       => null,
                    'almacen_id'    => $almacen->id,
                    'user_id'       => auth()->id(),
                    'provider_id'   => $provider?->id,
                    'supplier_name' => $supplierName,
                    'monto_pagado'  => collect($validated['items'])->sum(
                        fn($i) => (int) $i['cantidad'] * (float) $i['costo_unitario']
                    ),
                    'observaciones' => $validated['observaciones'] ?? null,
                    'estado'        => 'aprobado',
                    'aprobado_por'  => auth()->id(),
                    'aprobado_at'   => now(),
                ]);

                $auditOldValues = [];
                $auditNewValues = ['estado' => 'aprobado', 'almacen' => $almacen->nombre];

                // Sort by product_id for deterministic lock order (deadlock prevention)
                $sortedItems = collect($validated['items'])->sortBy('product_id')->values();

                foreach ($sortedItems as $item) {
                    InventoryReceiptItem::create([
                        'receipt_id'     => $receipt->id,
                        'product_id'     => $item['product_id'],
                        'cantidad'       => $item['cantidad'],
                        'costo_unitario' => $item['costo_unitario'],
                    ]);

                    $inventoryResult = $this->stockService->entrada(
                        productId:     (int) $item['product_id'],
                        cantidad:      (int) $item['cantidad'],
                        sedeId:        null,
                        almacenId:     $almacen->id,
                        costoUnitario: (float) $item['costo_unitario'],
                        userId:        auth()->id(),
                        motivo:        "Entrada mercancía — {$supplierName}",
                        referenceId:   $receipt->id,
                        referenceType: 'receipt',
                    );

                    $product  = Product::find($item['product_id']);
                    $oldStock = $inventoryResult->cantidad_stock - (int) $item['cantidad'];

                    $auditLabel = $product->nombre . " (#{$product->id})";
                    $auditOldValues[$auditLabel] = (int) $oldStock;
                    $auditNewValues[$auditLabel] = (int) $inventoryResult->cantidad_stock;

                    // Update running weighted-average cost (mirrors InventoryReceiptController::approve)
                    if ($oldStock + (int) $item['cantidad'] > 0) {
                        $newAvgCost = (($oldStock * (float) ($product->precio_compra ?? 0))
                            + ((int) $item['cantidad'] * (float) $item['costo_unitario']))
                            / ($oldStock + (int) $item['cantidad']);
                        $product->update(['precio_compra' => round($newAvgCost, 2)]);
                    }
                }

                ActivityLogger::log(
                    'warehouse.receipt',
                    "Entrada mercancía: {$almacen->nombre} — {$supplierName} — " . count($validated['items']) . " producto(s)",
                    $receipt,
                    $auditOldValues,
                    $auditNewValues,
                    null,
                    $almacen->id,
                );
            });
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error al registrar entrada: ' . $e->getMessage());
        }

        return redirect()->route('almacenes.show', $almacen)
            ->with('success', "Entrada de mercancía registrada en {$almacen->nombre}.");
    }

    // ── AJUSTE DE INVENTARIO ─────────────────────────────────────────────────

    public function adjustStock(Request $request, Almacen $almacen)
    {
        abort_unless(auth()->user()->isBoss(), 403);
        abort_unless($almacen->activo, 403);

        $validated = $request->validate([
            'product_id'   => 'required|exists:products,id',
            'new_cantidad' => 'required|integer|min:0',
            'motivo'       => 'required|string|min:3|max:500',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        $currentStock = (int) (Inventory::where('almacen_id', $almacen->id)
            ->whereNull('sede_id')
            ->where('product_id', $validated['product_id'])
            ->value('cantidad_stock') ?? 0);

        if ($currentStock === (int) $validated['new_cantidad']) {
            return back()->with('info', "Sin cambios: el stock de {$product->nombre} ya es {$currentStock}.");
        }

        try {
            DB::transaction(function () use ($validated, $almacen, $product, $currentStock) {
                $result = $this->stockService->setStockAbsolute(
                    productId:      (int) $validated['product_id'],
                    targetCantidad: (int) $validated['new_cantidad'],
                    sedeId:         null,
                    almacenId:      $almacen->id,
                    costoUnitario:  null,
                    userId:         auth()->id(),
                    motivo:         $validated['motivo'],
                );

                ActivityLogger::log(
                    'warehouse.adjustment',
                    "Ajuste inventario: {$almacen->nombre} — {$product->nombre} — {$currentStock} → {$result->cantidad_stock}",
                    null,
                    [$product->nombre => $currentStock],
                    [$product->nombre => (int) $result->cantidad_stock, 'motivo' => $validated['motivo']],
                    null,
                    $almacen->id,
                );
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Error al ajustar stock: ' . $e->getMessage());
        }

        return back()->with('success', "Stock de {$product->nombre} ajustado a {$validated['new_cantidad']}.");
    }
}
