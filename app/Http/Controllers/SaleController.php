<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class SaleController extends Controller
{
    public function create()
    {
        $sede = Auth::user()->sede;
        $products = Product::where('activo', true)->get();

        return view('operator.point-of-sale', [
            'sede' => $sede,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.precio_unitario' => 'required|numeric|min:0',
            'descuento' => 'nullable|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            $sede = Auth::user()->sede;
            $totalSistema = 0;
            $items = [];

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $subtotal = $item['cantidad'] * $item['precio_unitario'];
                $totalSistema += $subtotal;
                $items[] = $item;
            }

            $sale = Sale::create([
                'sede_id' => $sede->id,
                'user_id' => Auth::id(),
                'total_sistema' => $totalSistema,
                'descuento' => $validated['descuento'] ?? 0,
                'estado' => 'completada',
                'fecha_venta' => now(),
            ]);

            // Crear items de venta y actualizar inventario
            foreach ($items as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product_id'],
                    'cantidad' => $item['cantidad'],
                    'precio_unitario' => $item['precio_unitario'],
                    'subtotal' => $item['cantidad'] * $item['precio_unitario'],
                ]);

                // Actualizar inventario
                $inventory = Inventory::firstOrCreate(
                    ['product_id' => $item['product_id'], 'sede_id' => $sede->id],
                    ['cantidad_stock' => 0]
                );

                $inventory->decrement('cantidad_stock', $item['cantidad']);

                // Registrar movimiento
                InventoryMovement::create([
                    'product_id' => $item['product_id'],
                    'sede_id' => $sede->id,
                    'tipo' => 'salida',
                    'cantidad' => $item['cantidad'],
                    'motivo' => 'Venta',
                    'user_id' => Auth::id(),
                    'fecha_movimiento' => now(),
                ]);
            }

            return response()->json(['success' => true, 'sale_id' => $sale->id]);
        });
    }

    public function history()
    {
        $user = Auth::user();
        $query = Sale::with('items.product', 'user');

        if ($user->isOperator()) {
            $query->where('sede_id', $user->sede_id);
        }

        $sales = $query->latest('fecha_venta')->paginate(20);

        return view('sales.history', ['sales' => $sales]);
    }
}
