<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sede;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('inventory.view'), 403);

        $query = StockTransfer::with('fromSede', 'toSede', 'createdBy');

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
        $products = Product::where('activo', true)->orderBy('nombre')->get();

        return view('admin.transfers.create', compact('sedes', 'products'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('inventory.view'), 403);

        $validated = $request->validate([
            'from_sede_id'           => 'required|exists:sedes,id',
            'to_sede_id'             => 'required|exists:sedes,id|different:from_sede_id',
            'motivo'                 => 'nullable|string|max:500',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.cantidad'       => 'required|integer|min:1',
        ]);

        // Validate stock availability
        foreach ($validated['items'] as $item) {
            $stock = Inventory::where('product_id', $item['product_id'])
                ->where('sede_id', $validated['from_sede_id'])
                ->value('cantidad_stock') ?? 0;

            if ($stock < $item['cantidad']) {
                $product = Product::find($item['product_id']);
                return back()->withInput()->with('error',
                    "Stock insuficiente para \"{$product->nombre}\". Disponible: {$stock}.");
            }
        }

        DB::transaction(function () use ($validated) {
            $transfer = StockTransfer::create([
                'from_sede_id' => $validated['from_sede_id'],
                'to_sede_id'   => $validated['to_sede_id'],
                'created_by'   => auth()->id(),
                'motivo'       => $validated['motivo'] ?? null,
                'estado'       => 'pendiente',
            ]);

            foreach ($validated['items'] as $item) {
                StockTransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_id'  => $item['product_id'],
                    'cantidad'    => $item['cantidad'],
                ]);
            }

            ActivityLogger::log('transferencia.creada',
                "Transferencia creada: sede {$validated['from_sede_id']} → {$validated['to_sede_id']}",
                $transfer);
        });

        return redirect()->route('transfers.index')->with('success', 'Transferencia registrada. Pendiente de aprobación.');
    }

    public function show(StockTransfer $transfer)
    {
        abort_unless(auth()->user()->can('inventory.view'), 403);

        $transfer->load('fromSede', 'toSede', 'createdBy', 'approvedBy', 'items.product');

        return view('admin.transfers.show', compact('transfer'));
    }

    public function approve(Request $request, StockTransfer $transfer)
    {
        abort_unless(auth()->user()->can('products.create'), 403);

        if (! $transfer->isPending()) {
            return back()->with('error', 'Esta transferencia ya fue procesada.');
        }

        $validated = $request->validate([
            'notas_aprobacion' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($transfer, $validated) {
            $transfer->load('items.product');

            foreach ($transfer->items as $item) {
                // Deduct from origin
                $fromInventory = Inventory::firstOrCreate(
                    ['product_id' => $item->product_id, 'sede_id' => $transfer->from_sede_id],
                    ['cantidad_stock' => 0]
                );
                $fromInventory->decrement('cantidad_stock', $item->cantidad);

                // Add to destination
                $toInventory = Inventory::firstOrCreate(
                    ['product_id' => $item->product_id, 'sede_id' => $transfer->to_sede_id],
                    ['cantidad_stock' => 0]
                );
                $toInventory->increment('cantidad_stock', $item->cantidad);

                // Movement: salida from origin
                InventoryMovement::create([
                    'product_id'       => $item->product_id,
                    'sede_id'          => $transfer->from_sede_id,
                    'tipo'             => 'transferencia',
                    'cantidad'         => $item->cantidad,
                    'motivo'           => "Transferencia salida → " . ($transfer->toSede?->nombre ?? "sede {$transfer->to_sede_id}"),
                    'reference_id'     => $transfer->id,
                    'reference_type'   => 'transfer',
                    'user_id'          => auth()->id(),
                    'fecha_movimiento' => now(),
                ]);

                // Movement: entrada to destination
                InventoryMovement::create([
                    'product_id'       => $item->product_id,
                    'sede_id'          => $transfer->to_sede_id,
                    'tipo'             => 'entrada',
                    'cantidad'         => $item->cantidad,
                    'motivo'           => "Transferencia recibida ← " . ($transfer->fromSede?->nombre ?? "sede {$transfer->from_sede_id}"),
                    'reference_id'     => $transfer->id,
                    'reference_type'   => 'transfer',
                    'user_id'          => auth()->id(),
                    'fecha_movimiento' => now(),
                ]);
            }

            $transfer->update([
                'estado'           => 'aprobado',
                'approved_by'      => auth()->id(),
                'aprobado_at'      => now(),
                'notas_aprobacion' => $validated['notas_aprobacion'] ?? null,
            ]);

            ActivityLogger::log('transferencia.aprobada',
                "Transferencia aprobada: " . ($transfer->fromSede?->nombre ?? "sede {$transfer->from_sede_id}") . " → " . ($transfer->toSede?->nombre ?? "sede {$transfer->to_sede_id}"),
                $transfer);
        });

        return redirect()->route('transfers.index')->with('success', 'Transferencia aprobada. Stock actualizado.');
    }

    public function reject(Request $request, StockTransfer $transfer)
    {
        abort_unless(auth()->user()->can('products.create'), 403);

        if (! $transfer->isPending()) {
            return back()->with('error', 'Esta transferencia ya fue procesada.');
        }

        $validated = $request->validate(['notas_aprobacion' => 'required|string|max:500']);

        $transfer->update([
            'estado'           => 'rechazado',
            'approved_by'      => auth()->id(),
            'aprobado_at'      => now(),
            'notas_aprobacion' => $validated['notas_aprobacion'],
        ]);

        ActivityLogger::log('transferencia.rechazada',
            "Transferencia rechazada: " . ($transfer->fromSede?->nombre ?? "sede {$transfer->from_sede_id}") . " → " . ($transfer->toSede?->nombre ?? "sede {$transfer->to_sede_id}"),
            $transfer);

        return redirect()->route('transfers.index')->with('warning', 'Transferencia rechazada.');
    }
}
