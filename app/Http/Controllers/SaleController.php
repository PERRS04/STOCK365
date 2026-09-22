<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InventoryNotFoundException;
use App\Models\CashSession;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\ActivityLogger;
use App\Services\InventoryStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function __construct(private InventoryStockService $stockService) {}

    public function create()
    {
        abort_unless(auth()->user()->can('sales.create'), 403);
        // POS requires a sede context — warehouse operators and unassigned users cannot sell.
        abort_if(auth()->user()->sede_id === null, 403);

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
        // POS requires a sede context — warehouse operators and unassigned users cannot sell.
        abort_if(auth()->user()->sede_id === null, 403);

        $validated = $request->validate([
            'items'                    => 'required|array',
            'items.*.product_id'       => 'required|exists:products,id',
            'items.*.cantidad'         => 'required|integer|min:1',
            'items.*.precio_unitario'  => 'required|numeric|min:0',
            'descuento'                => 'nullable|numeric|min:0',
        ]);

        // Merge duplicate product_ids and sort ASC to prevent deadlocks under concurrency
        $items = $this->normalizeItems($validated['items']);

        try {
            return DB::transaction(function () use ($items, $validated) {
                $sede         = Auth::user()->sede;
                $descuento    = $validated['descuento'] ?? 0;
                $totalSistema = array_sum(
                    array_map(fn($i) => $i['cantidad'] * $i['precio_unitario'], $items)
                );

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

                foreach ($items as $item) {
                    $product = Product::find($item['product_id']);

                    SaleItem::create([
                        'sale_id'         => $sale->id,
                        'product_id'      => $item['product_id'],
                        'cantidad'        => $item['cantidad'],
                        'precio_unitario' => $item['precio_unitario'],
                        'costo_unitario'  => $product->precio_compra ?? 0,
                        'subtotal'        => $item['cantidad'] * $item['precio_unitario'],
                    ]);

                    $this->stockService->salida(
                        productId:     $item['product_id'],
                        cantidad:      $item['cantidad'],
                        sedeId:        $sede->id,
                        almacenId:     null,
                        costoUnitario: $product->precio_compra ?? null,
                        userId:        Auth::id(),
                        motivo:        'Venta',
                        referenceId:   $sale->id,
                        referenceType: 'sale',
                    );
                }

                ActivityLogger::log(
                    'sale.create',
                    "Venta registrada — {$sede->nombre} | " . count($items) . " productos | Total: \${$sale->total_sistema}",
                    $sale,
                    [],
                    ['total_sistema' => $sale->total_sistema, 'items_count' => count($items)]
                );

                return response()->json(['success' => true, 'sale_id' => $sale->id]);
            });
        } catch (InsufficientStockException $e) {
            return response()->json([
                'error' => "Stock insuficiente. Disponible: {$e->disponible}. Requerido: {$e->requerido}.",
            ], 422);
        } catch (InventoryNotFoundException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
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

    // Merge items that share the same product_id (sum quantities, keep first price),
    // then sort ASC by product_id to acquire row locks in a consistent order and
    // reduce deadlock risk when multiple transactions run concurrently.
    private function normalizeItems(array $rawItems): array
    {
        $consolidated = [];
        foreach ($rawItems as $item) {
            $pid = $item['product_id'];
            if (isset($consolidated[$pid])) {
                $consolidated[$pid]['cantidad'] += $item['cantidad'];
            } else {
                $consolidated[$pid] = $item;
            }
        }
        ksort($consolidated);
        return array_values($consolidated);
    }
}
