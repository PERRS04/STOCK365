<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\Provider;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\InventoryMovement;
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
            'items' => 'required|array',
            'items.*.id' => 'required|exists:purchase_order_items,id',
            'items.*.cantidad_recibida' => 'required|integer|min:0',
        ]);

        return DB::transaction(function () use ($validated, $order) {
            foreach ($validated['items'] as $item) {
                $poItem = $order->items()->findOrFail($item['id']);
                $poItem->update(['cantidad_recibida' => $item['cantidad_recibida']]);

                // Actualizar inventario para cada sede
                // Por ahora, asumimos que se distribuye en la sede principal
                $sedes = request('sedes', [1]); // Default a sede 1 si no especifica

                foreach ($sedes as $sedeId) {
                    $inventory = Inventory::firstOrCreate(
                        ['product_id' => $poItem->product_id, 'sede_id' => $sedeId],
                        ['cantidad_stock' => 0]
                    );

                    $inventory->increment('cantidad_stock', $item['cantidad_recibida']);

                    InventoryMovement::create([
                        'product_id' => $poItem->product_id,
                        'sede_id' => $sedeId,
                        'tipo' => 'entrada',
                        'cantidad' => $item['cantidad_recibida'],
                        'motivo' => 'Pedido de compra #' . $order->id,
                        'user_id' => Auth::id(),
                        'fecha_movimiento' => now(),
                    ]);
                }
            }

            $order->update(['estado' => 'recibido']);

            return redirect()->back()
                ->with('success', 'Mercadería recibida y stock actualizado');
        });
    }
}
