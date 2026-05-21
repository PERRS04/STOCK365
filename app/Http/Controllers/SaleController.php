<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function create()
    {
        abort_unless(auth()->user()->can('sales.create'), 403);

        $user = Auth::user();
        $cashSession = $user->isOperator()
            ? CashSession::activeForUser($user->id, $user->sede_id)
            : null;

        return view('operator.point-of-sale', [
            'sede'        => $user->sede,
            'cashSession' => $cashSession,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('sales.create'), 403);

        $validated = $request->validate([
            'items'                    => 'required|array',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.cantidad'         => 'required|integer|min:1',
            'items.*.precio_unitario'  => 'required|numeric|min:0',
            'descuento'                => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            $sede        = Auth::user()->sede;
            $totalSistema = 0;

            // Check stock availability before committing
            foreach ($validated['items'] as $item) {
                $stock = Inventory::where('product_id', $item['product_id'])
                    ->where('sede_id', $sede->id)
                    ->value('cantidad_stock') ?? 0;

                if ($stock < $item['cantidad']) {
                    $product = \App\Models\Product::find($item['product_id']);
                    return response()->json([
                        'error' => "Stock insuficiente para \"{$product->nombre}\". Disponible: {$stock}, solicitado: {$item['cantidad']}."
                    ], 422);
                }
            }

            foreach ($validated['items'] as $item) {
                $totalSistema += $item['cantidad'] * $item['precio_unitario'];
            }

            $descuento = $validated['descuento'] ?? 0;

            $cashSession = CashSession::activeForUser(Auth::id(), $sede->id);

            $sale = Sale::create([
                'sede_id'         => $sede->id,
                'user_id'         => Auth::id(),
                'cash_session_id' => $cashSession?->id,
                'total_sistema'   => $totalSistema - $descuento,
                'descuento'       => $descuento,
                'estado'          => 'completada',
                'fecha_venta'     => now(),
            ]);

            foreach ($validated['items'] as $item) {
                $product = \App\Models\Product::find($item['product_id']);

                SaleItem::create([
                    'sale_id'          => $sale->id,
                    'product_id'       => $item['product_id'],
                    'cantidad'         => $item['cantidad'],
                    'precio_unitario'  => $item['precio_unitario'],
                    'costo_unitario'   => $product->precio_compra ?? 0,
                    'subtotal'         => $item['cantidad'] * $item['precio_unitario'],
                ]);

                $inventory = Inventory::firstOrCreate(
                    ['product_id' => $item['product_id'], 'sede_id' => $sede->id],
                    ['cantidad_stock' => 0]
                );
                $inventory->decrement('cantidad_stock', $item['cantidad']);

                InventoryMovement::create([
                    'product_id'       => $item['product_id'],
                    'sede_id'          => $sede->id,
                    'tipo'             => 'salida',
                    'cantidad'         => $item['cantidad'],
                    'costo_unitario'   => $product->precio_compra ?? 0,
                    'motivo'           => 'Venta',
                    'reference_id'     => $sale->id,
                    'reference_type'   => 'sale',
                    'user_id'          => Auth::id(),
                    'fecha_movimiento' => now(),
                ]);
            }

            ActivityLogger::log(
                'sale.create',
                "Venta registrada — {$sede->nombre} | " . count($validated['items']) . " productos | Total: \${$sale->total_sistema}",
                $sale,
                [],
                ['total_sistema' => $sale->total_sistema, 'items_count' => count($validated['items'])]
            );

            return response()->json(['success' => true, 'sale_id' => $sale->id]);
        });
    }

    public function history()
    {
        abort_unless(auth()->user()->can('sales.view.own'), 403);

        $user  = Auth::user();
        $query = Sale::with('items.product', 'user');

        if ($user->isOperator()) {
            $query->where('sede_id', $user->sede_id);
        }

        $sales = $query->latest('fecha_venta')->paginate(20);

        return view('sales.history', compact('sales'));
    }
}
