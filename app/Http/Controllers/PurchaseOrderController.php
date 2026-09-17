<?php

namespace App\Http\Controllers;

use App\Models\InventoryReceipt;
use App\Models\PurchaseOrder;
use App\Models\Provider;
use App\Models\Product;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $this->authorize('boss');

        $orders = PurchaseOrder::with('provider', 'createdBy')
            ->latest('fecha_pedido')
            ->paginate(15);

        return view('admin.purchase-orders.index', ['orders' => $orders]);
    }

    public function create()
    {
        $this->authorize('boss');

        $providers = Provider::where('activo', true)->get();
        $products = Product::where('activo', true)->get();

        return view('admin.purchase-orders.create', [
            'providers' => $providers,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('boss');

        $validated = $request->validate([
            'provider_id' => 'required|exists:providers,id',
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.precio_unitario' => 'required|numeric|min:0',
            'fecha_estimada_entrega' => 'nullable|date|after:today',
            'observaciones' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $total = 0;
            foreach ($validated['items'] as $item) {
                $total += $item['cantidad'] * $item['precio_unitario'];
            }

            $order = PurchaseOrder::create([
                'provider_id' => $validated['provider_id'],
                'created_by' => Auth::id(),
                'fecha_pedido' => now(),
                'fecha_estimada_entrega' => $validated['fecha_estimada_entrega'] ?? null,
                'total' => $total,
                'estado' => 'pendiente',
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['cantidad'] * $item['precio_unitario'],
                ]);
            }

            return redirect()->route('purchase-orders.index')
                ->with('success', 'Pedido creado exitosamente');
        });
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('boss');
        $purchaseOrder->load('items.product', 'provider');
        return view('admin.purchase-orders.show', compact('purchaseOrder'));
    }

    public function markAsSent(PurchaseOrder $order)
    {
        $this->authorize('boss');

        $order->update(['estado' => 'enviado']);

        return redirect()->back()
            ->with('success', 'Pedido marcado como enviado');
    }

    public function receiveStock(Request $request, PurchaseOrder $order)
    {
        $this->authorize('boss');

        $validated = $request->validate([
            'items'                     => 'required|array|min:1',
            'items.*.id'                => 'required|integer',
            'items.*.cantidad_recibida' => 'required|integer|min:1',
        ]);

        try {
            $receipt = DB::transaction(function () use ($validated, $order) {
                // Lock PO to prevent concurrent receipts
                $lockedOrder = PurchaseOrder::query()
                    ->lockForUpdate()
                    ->findOrFail($order->id);

                // Re-validate estado under lock — never trust pre-lock state
                if ($lockedOrder->estado !== 'enviado') {
                    throw new \RuntimeException(
                        $lockedOrder->estado === 'recibido'
                            ? 'Este pedido ya fue recibido.'
                            : 'Solo se puede recibir un pedido en estado "enviado".'
                    );
                }

                // Anti-double-submit: reject if active receipt already exists
                $hasActiveReceipt = InventoryReceipt::where('purchase_order_id', $lockedOrder->id)
                    ->whereIn('estado', ['pendiente', 'aprobado'])
                    ->exists();

                if ($hasActiveReceipt) {
                    throw new \RuntimeException('Ya existe una recepción activa para este pedido.');
                }

                // Load PO items for ownership validation
                $poItemsById = $lockedOrder->items->keyBy('id');

                // Reject duplicate item IDs in the same request
                $inputIds = collect($validated['items'])->pluck('id');
                if ($inputIds->unique()->count() !== $inputIds->count()) {
                    throw new \RuntimeException('El pedido contiene ítems duplicados en la solicitud.');
                }

                // Validate ownership and quantities — all before any mutation
                $resolvedItems = [];
                foreach ($validated['items'] as $input) {
                    $poItem = $poItemsById->get((int) $input['id']);

                    if (! $poItem) {
                        throw new \RuntimeException(
                            "El ítem #{$input['id']} no pertenece a este pedido."
                        );
                    }

                    if ((int) $input['cantidad_recibida'] > $poItem->cantidad) {
                        throw new \RuntimeException(
                            "La cantidad recibida ({$input['cantidad_recibida']}) supera " .
                            "la cantidad ordenada ({$poItem->cantidad}) para el ítem #{$poItem->id}."
                        );
                    }

                    $resolvedItems[] = [
                        'poItem'            => $poItem,
                        'cantidad_recibida' => (int) $input['cantidad_recibida'],
                    ];
                }

                // Deterministic order: product_id ASC, then item id ASC (deadlock prevention prep)
                usort($resolvedItems, fn ($a, $b) =>
                    $a['poItem']->product_id !== $b['poItem']->product_id
                        ? $a['poItem']->product_id <=> $b['poItem']->product_id
                        : $a['poItem']->id <=> $b['poItem']->id
                );

                // Persist received quantities on PO items
                foreach ($resolvedItems as $item) {
                    $item['poItem']->update(['cantidad_recibida' => $item['cantidad_recibida']]);
                }

                $lockedOrder->load('provider');

                // Create pending receipt — no stock mutation, no payment (monto_pagado=0)
                $receipt = InventoryReceipt::create([
                    'purchase_order_id' => $lockedOrder->id,
                    'sede_id'           => null,
                    'user_id'           => auth()->id(),
                    'provider_id'       => $lockedOrder->provider_id,
                    'supplier_name'     => $lockedOrder->provider->nombre,
                    'monto_pagado'      => 0,
                    'observaciones'     => "Originado en pedido de compra #{$lockedOrder->id}",
                    'estado'            => 'pendiente',
                ]);

                ActivityLogger::log(
                    'purchase_order.receipt_created',
                    "Recepción #{$receipt->id} creada desde pedido #{$lockedOrder->id} · {$lockedOrder->provider->nombre}",
                    $receipt,
                    [],
                    ['purchase_order_id' => $lockedOrder->id]
                );

                return $receipt;
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('inventory-receipts.show', $receipt)
            ->with('success', 'Recepción creada. Pendiente de aprobación.');
    }
}
