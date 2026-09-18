<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\InventoryReceipt;
use App\Models\InventoryReceiptItem;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\ReceiptPaymentAllocation;
use App\Models\Sede;
use App\Models\StockAlert;
use App\Services\ActivityLogger;
use App\Services\InventoryStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InventoryReceiptController extends Controller
{
    public function __construct(private InventoryStockService $stockService) {}

    // ── OPERATOR: show create form ────────────────────────────────────────────

    public function create()
    {
        abort_unless(auth()->user()->can('receipts.create'), 403);

        $providers = \App\Models\Provider::where('activo', true)->orderBy('nombre')->get();

        $sedeActualId = auth()->user()->sede_id;
        $sedes = $sedeActualId
            ? Sede::where('id', '!=', $sedeActualId)->orderBy('nombre')->get()
            : Sede::orderBy('nombre')->get();

        return view('operator.inventory-receipt-create', compact('providers', 'sedes'));
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
            'provider_id'           => 'required|exists:providers,id',
            'monto_pagado'          => 'required|numeric|min:0.01',
            'observaciones'         => 'nullable|string|max:1000',
            'invoice_file'          => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
            'allocations'           => 'nullable|array|max:3',
            'allocations.*.sede_id' => 'required|exists:sedes,id',
            'allocations.*.monto'   => 'required|numeric|min:0.01',
        ]);

        // Validate allocation totals
        $allocations = array_filter($request->input('allocations', []), fn($a) => !empty($a['sede_id']) && !empty($a['monto']));
        if (!empty($allocations)) {
            $extTotal = collect($allocations)->sum('monto');

            if ($extTotal >= $validated['monto_pagado']) {
                return back()->withInput()->withErrors([
                    'allocations' => 'La suma de aportes externos debe ser menor al monto total.',
                ]);
            }

            $sedeIds = collect($allocations)->pluck('sede_id');
            if ($sedeIds->unique()->count() !== $sedeIds->count()) {
                return back()->withInput()->withErrors([
                    'allocations' => 'No puedes asignar dos aportes a la misma sede.',
                ]);
            }

            if ($sedeIds->contains((string) auth()->user()->sede_id)) {
                return back()->withInput()->withErrors([
                    'allocations' => 'No puedes asignarte un aporte externo a tu propia sede.',
                ]);
            }
        }

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

        foreach ($allocations as $alloc) {
            ReceiptPaymentAllocation::create([
                'inventory_receipt_id' => $receipt->id,
                'source_type'          => 'other_branch',
                'source_sede_id'       => $alloc['sede_id'],
                'amount'               => $alloc['monto'],
                'status'               => 'pending',
            ]);
        }

        $allocMsg = !empty($allocations)
            ? ' · Con ' . count($allocations) . ' aporte(s) de otras sedes pendientes.'
            : '';

        ActivityLogger::log(
            'recepcion.registrada',
            "Recepción registrada: {$provider->nombre} · \${$validated['monto_pagado']}" . (auth()->user()->sede ? " · " . auth()->user()->sede->nombre : "") . $allocMsg,
            $receipt
        );

        return redirect()->route('dashboard')
            ->with('success', 'Recepción registrada. Pendiente de aprobación por supervisión.' . $allocMsg);
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

        $receipt->load('sede', 'user', 'items.product', 'approvedBy', 'paymentAllocations.sourceSede');
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
            'notas_aprobacion'       => 'nullable|string|max:1000',
            'sede_id_override'       => 'nullable|exists:sedes,id',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.cantidad'       => 'required|integer|min:1',
            'items.*.costo_unitario' => 'required|numeric|min:0',
        ]);

        $calculatedTotal = round(
            collect($validated['items'])->sum(
                fn($item) => (float) $item['cantidad'] * (float) $item['costo_unitario']
            ),
            2
        );
        $montoPagado = round((float) $receipt->monto_pagado, 2);

        $isPurchaseOrderReceipt = $receipt->purchase_order_id !== null;

        if (! $isPurchaseOrderReceipt && abs($calculatedTotal - $montoPagado) > 0.005) {
            return back()
                ->withInput()
                ->with('audit_monto_pagado',     $montoPagado)
                ->with('audit_monto_calculado',  $calculatedTotal)
                ->with('audit_monto_diferencia', round($montoPagado - $calculatedTotal, 2))
                ->withErrors([
                    'monto_items' => 'El total de ítems debe coincidir exactamente con el monto pagado registrado.',
                ]);
        }

        try {
            DB::transaction(function () use ($receipt, $validated) {
                // Authoritative lock — prevents double-approval race condition
                $lockedReceipt = InventoryReceipt::query()->lockForUpdate()->findOrFail($receipt->id);

                if (! $lockedReceipt->isPending()) {
                    throw new \RuntimeException('Esta recepción ya fue procesada.');
                }

                // sede_id_override inside transaction so the UPDATE is atomic with stock changes
                if ($lockedReceipt->sede_id === null) {
                    if (empty($validated['sede_id_override'])) {
                        throw new \RuntimeException('Esta recepción no tiene sede asignada. Selecciona la sede de destino antes de aprobar.');
                    }
                    $lockedReceipt->update(['sede_id' => $validated['sede_id_override']]);
                    $lockedReceipt->refresh();
                }

                // ── PO-receipt: lock PO, validate items, check state ────────────
                $lockedOrder = null;
                if ($lockedReceipt->purchase_order_id !== null) {
                    $lockedOrder = PurchaseOrder::query()
                        ->lockForUpdate()
                        ->findOrFail($lockedReceipt->purchase_order_id);

                    if ($lockedOrder->estado !== 'enviado') {
                        throw new \RuntimeException(
                            $lockedOrder->estado === 'recibido'
                                ? 'El pedido vinculado ya fue marcado como recibido.'
                                : 'El pedido vinculado no está en estado "enviado".'
                        );
                    }

                    $expectedByProduct = $lockedOrder->items
                        ->filter(fn($item) => $item->cantidad_recibida > 0)
                        ->groupBy('product_id')
                        ->map(fn($group) => $group->sum('cantidad_recibida'));

                    if ($expectedByProduct->isEmpty()) {
                        throw new \RuntimeException('El pedido no tiene cantidades registradas para recepción.');
                    }

                    $actualByProduct = collect($validated['items'])
                        ->groupBy(fn($item) => (int) $item['product_id'])
                        ->map(fn($group) => $group->sum('cantidad'));

                    $expectedKeys = $expectedByProduct->keys()->map(fn($k) => (int) $k)->sort()->values()->toArray();
                    $actualKeys   = $actualByProduct->keys()->map(fn($k) => (int) $k)->sort()->values()->toArray();

                    if ($expectedKeys !== $actualKeys) {
                        throw new \RuntimeException('Los productos enviados no corresponden a los del pedido de compra.');
                    }

                    foreach ($expectedByProduct as $productId => $expectedQty) {
                        $actualQty = (int) $actualByProduct->get((int) $productId, 0);
                        if ($actualQty !== (int) $expectedQty) {
                            throw new \RuntimeException(
                                "La cantidad aprobada para el producto #{$productId} ({$actualQty}) " .
                                "no coincide con la recibida en el pedido ({$expectedQty})."
                            );
                        }
                    }
                }
                // ────────────────────────────────────────────────────────────────

                // Sort ASC by product_id for deterministic lock acquisition (deadlock prevention)
                $items = collect($validated['items'])->sortBy('product_id')->values();

                foreach ($items as $item) {
                    InventoryReceiptItem::create([
                        'receipt_id'     => $lockedReceipt->id,
                        'product_id'     => $item['product_id'],
                        'cantidad'       => $item['cantidad'],
                        'costo_unitario' => $item['costo_unitario'],
                    ]);

                    $inventoryResult = $this->stockService->entrada(
                        productId:     (int) $item['product_id'],
                        cantidad:      (int) $item['cantidad'],
                        sedeId:        $lockedReceipt->sede_id,
                        almacenId:     null,
                        costoUnitario: (float) $item['costo_unitario'],
                        userId:        auth()->id(),
                        motivo:        "Recepción mercancía — {$lockedReceipt->supplier_name}",
                        referenceId:   $lockedReceipt->id,
                        referenceType: 'receipt',
                    );

                    $product  = Product::find($item['product_id']);
                    $oldStock = $inventoryResult->cantidad_stock - $item['cantidad'];

                    if ($oldStock + $item['cantidad'] > 0) {
                        $newAvgCost = (($oldStock * ($product->precio_compra ?? 0)) + ($item['cantidad'] * $item['costo_unitario']))
                            / ($oldStock + $item['cantidad']);
                        $product->update(['precio_compra' => round($newAvgCost, 2)]);
                    }

                    if ($inventoryResult->cantidad_stock >= $product->stock_minimo) {
                        StockAlert::where('product_id', $product->id)
                            ->where('sede_id', $lockedReceipt->sede_id)
                            ->update(['alerta_activa' => false]);
                    }
                }

                $lockedReceipt->update([
                    'estado'           => 'aprobado',
                    'aprobado_por'     => auth()->id(),
                    'aprobado_at'      => now(),
                    'notas_aprobacion' => $validated['notas_aprobacion'] ?? null,
                ]);

                // ── Register supplier payment — direct receipts only ─────────────
                if ($lockedReceipt->purchase_order_id === null) {
                    $externalTotal = (float) ReceiptPaymentAllocation::where('inventory_receipt_id', $lockedReceipt->id)
                        ->where('source_type', 'other_branch')
                        ->sum('amount');

                    $localAmount = max(0, (float) $lockedReceipt->monto_pagado - $externalTotal);

                    if ($localAmount > 0) {
                        $activeSession = CashSession::where('sede_id', $lockedReceipt->sede_id)
                            ->whereIn('status', ['open', 'pending_closing'])
                            ->latest('opened_at')
                            ->first();

                        $movement = CashMovement::create([
                            'sede_id'         => $lockedReceipt->sede_id,
                            'user_id'         => $lockedReceipt->user_id,
                            'cash_session_id' => $activeSession?->id,
                            'type'            => 'pago_proveedor',
                            'amount'          => $localAmount,
                            'motivo'          => "Pago proveedor: {$lockedReceipt->supplier_name}",
                            'observaciones'   => "Recepción #{$lockedReceipt->id} aprobada",
                            'status'          => $activeSession ? 'aprobado' : 'pendiente',
                            'approved_by'     => $activeSession ? auth()->id() : null,
                            'approved_at'     => $activeSession ? now() : null,
                        ]);

                        if (! $activeSession) {
                            ActivityLogger::log(
                                'receipt.payment.deferred',
                                "Pago proveedor diferido — sin sesión activa. Recepción #{$lockedReceipt->id} · {$lockedReceipt->supplier_name} · \${$localAmount}" . ($lockedReceipt->sede ? ' · ' . $lockedReceipt->sede->nombre : ''),
                                $lockedReceipt,
                                [],
                                [
                                    'receipt_id'       => $lockedReceipt->id,
                                    'cash_movement_id' => $movement->id,
                                    'sede_id'          => $lockedReceipt->sede_id,
                                    'sede'             => $lockedReceipt->sede?->nombre,
                                    'proveedor'        => $lockedReceipt->supplier_name,
                                    'monto'            => $localAmount,
                                    'aprobador'        => auth()->user()->name,
                                    'aprobado_at'      => now()->toDateTimeString(),
                                ]
                            );
                        }
                    }
                }
                // ────────────────────────────────────────────────────────────────

                if ($lockedOrder !== null) {
                    $lockedOrder->update(['estado' => 'recibido']);
                }

                ActivityLogger::log(
                    'recepcion.aprobada',
                    "Recepción aprobada: {$lockedReceipt->supplier_name} · \${$lockedReceipt->monto_pagado}" . ($lockedReceipt->sede ? " · " . $lockedReceipt->sede->nombre : ""),
                    $lockedReceipt
                );
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

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

        $receipt->load('sede', 'user');

        $receipt->update([
            'estado'           => 'rechazado',
            'aprobado_por'     => auth()->id(),
            'aprobado_at'      => now(),
            'notas_aprobacion' => $validated['notas_aprobacion'],
        ]);

        ActivityLogger::log(
            'recepcion.rechazada',
            "Recepción rechazada: {$receipt->supplier_name} · \${$receipt->monto_pagado}" . ($receipt->sede ? " · " . $receipt->sede->nombre : ""),
            $receipt,
            [
                'estado'         => 'pendiente',
                'monto_pagado'   => $receipt->monto_pagado,
                'proveedor'      => $receipt->supplier_name,
                'sede'           => $receipt->sede?->nombre,
                'registrado_por' => $receipt->user?->name,
                'registrado_at'  => $receipt->created_at?->toDateTimeString(),
            ],
            [
                'estado'        => 'rechazado',
                'rechazado_por' => auth()->user()->name,
                'rechazado_at'  => now()->toDateTimeString(),
                'motivo'        => $validated['notas_aprobacion'],
            ]
        );

        return redirect()->route('inventory-receipts.index')
            ->with('warning', 'Recepción rechazada.');
    }
}
