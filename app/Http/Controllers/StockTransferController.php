<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InventoryNotFoundException;
use App\Models\Almacen;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Sede;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Services\ActivityLogger;
use App\Services\InventoryStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function __construct(private InventoryStockService $stockService) {}

    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('inventory.view'), 403);

        $query = StockTransfer::with('fromSede', 'toSede', 'fromAlmacen', 'toAlmacen', 'createdBy');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $transfers = $query->latest()->paginate(20)->withQueryString();

        $pendingCount = StockTransfer::where('estado', 'pendiente')->count();

        return view('admin.transfers.index', compact('transfers', 'pendingCount'));
    }

    public function create()
    {
        abort_unless(auth()->user()->can('inventory.view'), 403);

        $sedes    = Sede::orderBy('nombre')->get();
        $almacenes = Almacen::where('activo', true)->orderBy('nombre')->get();
        $products = Product::where('activo', true)->orderBy('nombre')->get();

        return view('admin.transfers.create', compact('sedes', 'almacenes', 'products'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('inventory.view'), 403);

        $validated = $request->validate([
            'from_sede_id'       => 'nullable|exists:sedes,id',
            'from_almacen_id'    => 'nullable|exists:almacenes,id',
            'to_sede_id'         => 'nullable|exists:sedes,id',
            'to_almacen_id'      => 'nullable|exists:almacenes,id',
            'motivo'             => 'nullable|string|max:500',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.cantidad'   => 'required|integer|min:1',
        ]);

        $fromSedeId    = (int) ($validated['from_sede_id']    ?? 0) ?: null;
        $fromAlmacenId = (int) ($validated['from_almacen_id'] ?? 0) ?: null;
        $toSedeId      = (int) ($validated['to_sede_id']      ?? 0) ?: null;
        $toAlmacenId   = (int) ($validated['to_almacen_id']   ?? 0) ?: null;

        // XOR: exactly one origin location
        if (($fromSedeId !== null) === ($fromAlmacenId !== null)) {
            return back()->withInput()->withErrors(
                ['from' => 'Selecciona exactamente un origen: Sede o Almacén (no ambos, no ninguno).']
            );
        }

        // XOR: exactly one destination location
        if (($toSedeId !== null) === ($toAlmacenId !== null)) {
            return back()->withInput()->withErrors(
                ['to' => 'Selecciona exactamente un destino: Sede o Almacén (no ambos, no ninguno).']
            );
        }

        // Same-location guard
        if ($fromSedeId !== null && $fromSedeId === $toSedeId) {
            return back()->withInput()->withErrors(
                ['to_sede_id' => 'El origen y destino no pueden ser la misma sede.']
            );
        }
        if ($fromAlmacenId !== null && $fromAlmacenId === $toAlmacenId) {
            return back()->withInput()->withErrors(
                ['to_almacen_id' => 'El origen y destino no pueden ser el mismo almacén.']
            );
        }

        // Duplicate product guard
        $productIds = collect($validated['items'])->pluck('product_id');
        if ($productIds->unique()->count() !== $productIds->count()) {
            return back()->withInput()->with('error', 'No puedes incluir el mismo producto más de una vez.');
        }

        // Pre-check stock at origin (fast UX feedback — authoritative check happens in approve())
        foreach ($validated['items'] as $item) {
            $stock = Inventory::where([
                'product_id' => $item['product_id'],
                'sede_id'    => $fromSedeId,
                'almacen_id' => $fromAlmacenId,
            ])->value('cantidad_stock') ?? 0;

            if ($stock < $item['cantidad']) {
                $product = Product::find($item['product_id']);
                return back()->withInput()->with('error',
                    "Stock insuficiente para \"{$product->nombre}\". Disponible: {$stock}.");
            }
        }

        DB::transaction(function () use ($validated, $fromSedeId, $fromAlmacenId, $toSedeId, $toAlmacenId) {
            $transfer = StockTransfer::create([
                'from_sede_id'    => $fromSedeId,
                'from_almacen_id' => $fromAlmacenId,
                'to_sede_id'      => $toSedeId,
                'to_almacen_id'   => $toAlmacenId,
                'created_by'      => auth()->id(),
                'motivo'          => $validated['motivo'] ?? null,
                'estado'          => 'pendiente',
            ]);

            foreach ($validated['items'] as $item) {
                StockTransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_id'  => $item['product_id'],
                    'cantidad'    => $item['cantidad'],
                ]);
            }

            ActivityLogger::log(
                'transferencia.creada',
                "Transferencia #{$transfer->id} creada: {$transfer->fromLocationName()} → {$transfer->toLocationName()}",
                $transfer
            );
        });

        return redirect()->route('transfers.index')->with('success', 'Transferencia registrada. Pendiente de aprobación.');
    }

    public function show(StockTransfer $transfer)
    {
        abort_unless(auth()->user()->can('inventory.view'), 403);

        $transfer->load('fromSede', 'toSede', 'fromAlmacen', 'toAlmacen', 'createdBy', 'approvedBy', 'items.product');

        return view('admin.transfers.show', compact('transfer'));
    }

    public function approve(Request $request, StockTransfer $transfer)
    {
        abort_unless(auth()->user()->can('products.create'), 403);

        $validated = $request->validate([
            'notas_aprobacion' => 'nullable|string|max:500',
        ]);

        try {
            DB::transaction(function () use ($transfer, $validated) {
                $locked = StockTransfer::query()
                    ->lockForUpdate()
                    ->findOrFail($transfer->id);

                if (! $locked->isPending()) {
                    throw new \RuntimeException('Esta transferencia ya fue procesada.');
                }

                $locked->load('items', 'fromSede', 'fromAlmacen', 'toSede', 'toAlmacen');

                $fromLabel = $locked->fromLocationName();
                $toLabel   = $locked->toLocationName();
                $userId    = auth()->id();

                // Sort by product_id ASC for deterministic lock order (deadlock prevention)
                $sortedItems = $locked->items->sortBy('product_id')->values();

                foreach ($sortedItems as $item) {
                    $this->stockService->salida(
                        productId:     $item->product_id,
                        cantidad:      $item->cantidad,
                        sedeId:        $locked->from_sede_id,
                        almacenId:     $locked->from_almacen_id,
                        costoUnitario: null,
                        userId:        $userId,
                        motivo:        "Transferencia salida → {$toLabel}",
                        referenceId:   $locked->id,
                        referenceType: 'transfer',
                    );

                    $this->stockService->entrada(
                        productId:     $item->product_id,
                        cantidad:      $item->cantidad,
                        sedeId:        $locked->to_sede_id,
                        almacenId:     $locked->to_almacen_id,
                        costoUnitario: null,
                        userId:        $userId,
                        motivo:        "Transferencia recibida ← {$fromLabel}",
                        referenceId:   $locked->id,
                        referenceType: 'transfer',
                    );
                }

                $locked->update([
                    'estado'           => 'aprobado',
                    'approved_by'      => $userId,
                    'aprobado_at'      => now(),
                    'notas_aprobacion' => $validated['notas_aprobacion'] ?? null,
                ]);

                ActivityLogger::log(
                    'transferencia.aprobada',
                    "Transferencia #{$locked->id} aprobada: {$fromLabel} → {$toLabel}",
                    $locked
                );
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('transfers.index')->with('success', 'Transferencia aprobada. Stock actualizado.');
    }

    public function reject(Request $request, StockTransfer $transfer)
    {
        abort_unless(auth()->user()->can('products.create'), 403);

        $validated = $request->validate(['notas_aprobacion' => 'required|string|max:500']);

        try {
            DB::transaction(function () use ($transfer, $validated) {
                $locked = StockTransfer::query()
                    ->lockForUpdate()
                    ->findOrFail($transfer->id);

                if (! $locked->isPending()) {
                    throw new \RuntimeException('Esta transferencia ya fue procesada.');
                }

                $locked->load('fromSede', 'fromAlmacen', 'toSede', 'toAlmacen');

                $locked->update([
                    'estado'           => 'rechazado',
                    'approved_by'      => auth()->id(),
                    'aprobado_at'      => now(),
                    'notas_aprobacion' => $validated['notas_aprobacion'],
                ]);

                ActivityLogger::log(
                    'transferencia.rechazada',
                    "Transferencia #{$locked->id} rechazada: {$locked->fromLocationName()} → {$locked->toLocationName()}",
                    $locked
                );
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('transfers.index')->with('warning', 'Transferencia rechazada.');
    }
}
