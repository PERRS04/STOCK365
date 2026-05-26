<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\InventoryReceipt;
use App\Models\InventoryReceiptItem;
use App\Models\Product;
use App\Models\StockAlert;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InventoryReceiptController extends Controller
{
    // ── OPERATOR: show create form ────────────────────────────────────────────

    public function create()
    {
        abort_unless(auth()->user()->can('receipts.create'), 403);

        $providers = \App\Models\Provider::where('activo', true)->orderBy('nombre')->get();
        return view('operator.inventory-receipt-create', compact('providers'));
    }

    // ── OPERATOR: store receipt ───────────────────────────────────────────────

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('receipts.create'), 403);

        if (auth()->user()->sede_id === null) {
            return redirect()->back()
                ->withErrors(['sede_id' => 'Necesitas tener una sede asignada para registrar recepciones. Pide al administrador que te asigne una sede.']);
        }

        $validated = $request->validate([
            'provider_id'   => 'required|exists:providers,id',
            'monto_pagado'  => 'required|numeric|min:0.01',
            'observaciones' => 'nullable|string|max:1000',
            'invoice_file'  => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
        ]);

        $provider = \App\Models\Provider::find($validated['provider_id']);

        $invoicePath = null;
        if ($request->hasFile('invoice_file')) {
            $invoicePath = $request->file('invoice_file')->store('invoices', 'public');
        }

        $receipt = InventoryReceipt::create([
            'sede_id'       => auth()->user()->sede_id,
            'user_id'       => auth()->id(),
            'provider_id'   => $validated['provider_id'],
            'supplier_name' => $provider->nombre,
            'monto_pagado'  => $validated['monto_pagado'],
            'observaciones' => $validated['observaciones'] ?? null,
            'invoice_path'  => $invoicePath,
            'estado'        => 'pendiente',
        ]);

        ActivityLogger::log(
            'recepcion.registrada',
            "Recepción registrada: {$provider->nombre} · \${$validated['monto_pagado']}" . (auth()->user()->sede ? " · " . auth()->user()->sede->nombre : ""),
            $receipt
        );

        return redirect()->route('dashboard')
            ->with('success', 'Recepción registrada. Pendiente de aprobación por supervisión.');
    }

    // ── ADMIN: list receipts ──────────────────────────────────────────────────

    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('receipts.approve'), 403);

        $estado = $request->get('estado', 'pendiente');

        $receipts = InventoryReceipt::with('sede', 'user')
            ->when($estado !== 'all', fn($q) => $q->where('estado', $estado))
            ->latest()
            ->paginate(20);

        $pendingCount  = InventoryReceipt::where('estado', 'pendiente')->count();
        $approvedCount = InventoryReceipt::where('estado', 'aprobado')->count();
        $rejectedCount = InventoryReceipt::where('estado', 'rechazado')->count();

        return view('admin.inventory-receipts.index', compact(
            'receipts', 'estado', 'pendingCount', 'approvedCount', 'rejectedCount'
        ));
    }

    // ── ADMIN: show receipt + approval form ───────────────────────────────────

    public function show(InventoryReceipt $receipt)
    {
        abort_unless(auth()->user()->can('receipts.approve'), 403);

        $receipt->load('sede', 'user', 'items.product', 'approvedBy');
        $products = Product::where('activo', true)->orderBy('nombre')->get();

        return view('admin.inventory-receipts.show', compact('receipt', 'products'));
    }

    // ── ADMIN: approve + update stock ─────────────────────────────────────────

    public function approve(Request $request, InventoryReceipt $receipt)
    {
        abort_unless(auth()->user()->can('receipts.approve'), 403);

        if (! $receipt->isPending()) {
            return back()->with('error', 'Esta recepción ya fue procesada.');
        }

        $validated = $request->validate([
            'notas_aprobacion'          => 'nullable|string|max:1000',
            'sede_id_override'          => 'nullable|exists:sedes,id',
            'items'                     => 'required|array|min:1',
            'items.*.product_id'        => 'required|exists:products,id',
            'items.*.cantidad'          => 'required|integer|min:1',
            'items.*.costo_unitario'    => 'required|numeric|min:0',
        ]);

        // Receipts created by the boss (sede_id = null) need a sede assigned at approval time.
        if ($receipt->sede_id === null) {
            if (empty($validated['sede_id_override'])) {
                return back()->withErrors(['sede_id_override' => 'Esta recepción no tiene sede asignada. Selecciona la sede de destino antes de aprobar.']);
            }
            $receipt->update(['sede_id' => $validated['sede_id_override']]);
            $receipt->refresh();
        }

        DB::transaction(function () use ($receipt, $validated) {
            foreach ($validated['items'] as $item) {
                InventoryReceiptItem::create([
                    'receipt_id'     => $receipt->id,
                    'product_id'     => $item['product_id'],
                    'cantidad'       => $item['cantidad'],
                    'costo_unitario' => $item['costo_unitario'],
                ]);

                $inventory = Inventory::firstOrCreate(
                    ['product_id' => $item['product_id'], 'sede_id' => $receipt->sede_id],
                    ['cantidad_stock' => 0]
                );

                $stockNuevo = $inventory->cantidad_stock + $item['cantidad'];

                $inventory->update([
                    'cantidad_stock'       => $stockNuevo,
                    'ultima_actualizacion' => now(),
                ]);

                $product = Product::find($item['product_id']);

                InventoryMovement::create([
                    'product_id'       => $item['product_id'],
                    'sede_id'          => $receipt->sede_id,
                    'tipo'             => 'entrada',
                    'cantidad'         => $item['cantidad'],
                    'costo_unitario'   => $item['costo_unitario'],
                    'motivo'           => "Recepción mercancía — {$receipt->supplier_name}",
                    'reference_id'     => $receipt->id,
                    'reference_type'   => 'receipt',
                    'user_id'          => auth()->id(),
                    'fecha_movimiento' => now(),
                ]);

                // Update product's weighted average cost
                $oldStock = $inventory->cantidad_stock - $item['cantidad'];
                if ($oldStock + $item['cantidad'] > 0) {
                    $newAvgCost = (($oldStock * ($product->precio_compra ?? 0)) + ($item['cantidad'] * $item['costo_unitario']))
                        / ($oldStock + $item['cantidad']);
                    $product->update(['precio_compra' => round($newAvgCost, 2)]);
                }

                if ($stockNuevo >= $product->stock_minimo) {
                    StockAlert::where('product_id', $product->id)
                        ->where('sede_id', $receipt->sede_id)
                        ->update(['alerta_activa' => false]);
                }
            }

            $receipt->update([
                'estado'           => 'aprobado',
                'aprobado_por'     => auth()->id(),
                'aprobado_at'      => now(),
                'notas_aprobacion' => $validated['notas_aprobacion'] ?? null,
            ]);

            ActivityLogger::log(
                'recepcion.aprobada',
                "Recepción aprobada: {$receipt->supplier_name} · \${$receipt->monto_pagado}" . ($receipt->sede ? " · " . $receipt->sede->nombre : ""),
                $receipt
            );
        });

        return redirect()->route('inventory-receipts.index')
            ->with('success', 'Recepción aprobada. Inventario actualizado correctamente.');
    }

    // ── ADMIN: reject ─────────────────────────────────────────────────────────

    public function reject(Request $request, InventoryReceipt $receipt)
    {
        abort_unless(auth()->user()->can('receipts.approve'), 403);

        if (! $receipt->isPending()) {
            return back()->with('error', 'Esta recepción ya fue procesada.');
        }

        $validated = $request->validate([
            'notas_aprobacion' => 'required|string|max:1000',
        ]);

        $receipt->update([
            'estado'           => 'rechazado',
            'aprobado_por'     => auth()->id(),
            'aprobado_at'      => now(),
            'notas_aprobacion' => $validated['notas_aprobacion'],
        ]);

        ActivityLogger::log(
            'recepcion.rechazada',
            "Recepción rechazada: {$receipt->supplier_name}" . ($receipt->sede ? " · " . $receipt->sede->nombre : ""),
            $receipt
        );

        return redirect()->route('inventory-receipts.index')
            ->with('warning', 'Recepción rechazada.');
    }
}
