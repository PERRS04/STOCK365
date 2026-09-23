<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InventoryNotFoundException;
use App\Models\CashSession;
use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\ActivityLogger;
use App\Services\InventoryStockService;
use App\Services\PriceResolverService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function __construct(
        private InventoryStockService $stockService,
        private PriceResolverService  $priceResolver,
    ) {}

    public function create()
    {
        abort_unless(auth()->user()->can('sales.create'), 403);
        // POS requires a sede context — warehouse operators and unassigned users cannot sell.
        abort_if(auth()->user()->sede_id === null, 403);

        $user = Auth::user();
        $cashSession = $user->isOperator()
            ? CashSession::activeForUser($user->id, $user->sede_id)
            : null;

        $sede     = $user->sede;
        $products = Product::where('activo', true)->get();

        $presentationsMap = $products
            ->mapWithKeys(fn ($p) => [
                $p->id => $this->priceResolver->presentationsForSede($p, $sede->id),
            ])
            ->filter(fn ($ps) => $ps->isNotEmpty())
            ->toArray();

        return view('operator.point-of-sale', [
            'sede'             => $sede,
            'cashSession'      => $cashSession,
            'presentationsMap' => $presentationsMap,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('sales.create'), 403);
        // POS requires a sede context — warehouse operators and unassigned users cannot sell.
        abort_if(auth()->user()->sede_id === null, 403);

        $validated = $request->validate([
            'items'                           => 'required|array',
            'items.*.product_id'              => 'required|exists:products,id',
            'items.*.presentation_id'         => 'nullable|integer',
            // Accept both new (cantidad_presentaciones) and legacy (cantidad) field names.
            // At least one must be present.
            'items.*.cantidad_presentaciones' => 'nullable|integer|min:1',
            'items.*.cantidad'                => 'nullable|integer|min:1',
            'descuento'                       => 'nullable|numeric|min:0',
        ]);

        // Normalise: unify cantidad_presentaciones / cantidad into cantidad_presentaciones
        foreach ($validated['items'] as &$rawItem) {
            if (empty($rawItem['cantidad_presentaciones'])) {
                $rawItem['cantidad_presentaciones'] = $rawItem['cantidad'] ?? 1;
            }
        }
        unset($rawItem);

        $sede = Auth::user()->sede;

        // Resolve prices server-side and build enriched items
        $enrichedItems = [];
        foreach ($validated['items'] as $raw) {
            $product        = Product::findOrFail($raw['product_id']);
            $presentationId = $raw['presentation_id'] ?? null;
            $qtyPres        = (int) $raw['cantidad_presentaciones'];

            if ($presentationId !== null) {
                $presentation = ProductPresentation::find($presentationId);

                if (!$presentation || $presentation->product_id !== $product->id) {
                    return response()->json([
                        'error' => "La presentación #{$presentationId} no pertenece al producto #{$product->id}.",
                    ], 422);
                }

                if (!$presentation->activo) {
                    return response()->json([
                        'error' => "La presentación «{$presentation->nombre}» está desactivada.",
                    ], 422);
                }

                $resolvedPrice = $this->priceResolver->forPresentation($presentation, $sede->id);

                if ($resolvedPrice === null) {
                    return response()->json([
                        'error' => "La presentación «{$presentation->nombre}» no tiene precio configurado para esta sede.",
                    ], 422);
                }

                $cantidadBase = $qtyPres * $presentation->factor_stock;

                $enrichedItems[] = [
                    'product_id'              => $product->id,
                    'presentation_id'         => $presentation->id,
                    'presentation_name'       => $presentation->nombre,
                    'presentation_factor'     => $presentation->factor_stock,
                    'cantidad_presentaciones' => $qtyPres,
                    'cantidad'                => $cantidadBase,
                    'precio_unitario'         => $resolvedPrice,
                    'subtotal'                => $qtyPres * $resolvedPrice,
                    'costo_unitario'          => (float) ($product->precio_compra ?? 0),
                ];
            } else {
                $resolvedPrice = $this->priceResolver->forProduct($product, $sede->id);

                $enrichedItems[] = [
                    'product_id'              => $product->id,
                    'presentation_id'         => null,
                    'presentation_name'       => null,
                    'presentation_factor'     => null,
                    'cantidad_presentaciones' => $qtyPres,
                    'cantidad'                => $qtyPres,
                    'precio_unitario'         => $resolvedPrice,
                    'subtotal'                => $qtyPres * $resolvedPrice,
                    'costo_unitario'          => (float) ($product->precio_compra ?? 0),
                ];
            }
        }

        // Merge duplicate (product_id, presentation_id) pairs, sort for deadlock avoidance
        $items = $this->normalizeItems($enrichedItems);

        try {
            return DB::transaction(function () use ($items, $validated, $sede) {
                $descuento    = $validated['descuento'] ?? 0;
                $totalSistema = array_sum(array_map(fn ($i) => $i['subtotal'], $items));

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
                    SaleItem::create([
                        'sale_id'                 => $sale->id,
                        'product_id'              => $item['product_id'],
                        'presentation_id'         => $item['presentation_id'],
                        'presentation_name'       => $item['presentation_name'],
                        'presentation_factor'     => $item['presentation_factor'],
                        'cantidad_presentaciones' => $item['cantidad_presentaciones'],
                        'cantidad'                => $item['cantidad'],
                        'precio_unitario'         => $item['precio_unitario'],
                        'costo_unitario'          => $item['costo_unitario'],
                        'subtotal'                => $item['subtotal'],
                    ]);

                    $this->stockService->salida(
                        productId:     $item['product_id'],
                        cantidad:      $item['cantidad'],   // always base units
                        sedeId:        $sede->id,
                        almacenId:     null,
                        costoUnitario: $item['costo_unitario'] ?: null,
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

    public function ticket(Sale $sale)
    {
        $user = auth()->user();
        abort_unless($user->can('sales.view.own'), 403);

        // IDOR guard: operators may only print tickets for their own sede.
        if ($user->isOperator() && $sale->sede_id !== $user->sede_id) {
            abort(403);
        }

        $sale->load(['items.product', 'user', 'sede']);

        return view('sales.ticket', compact('sale'));
    }

    /**
     * Merge items sharing the same (product_id, presentation_id) composite key,
     * summing cantidad_presentaciones and cantidad. Sort by composite key to prevent
     * deadlocks when multiple transactions run concurrently.
     */
    private function normalizeItems(array $rawItems): array
    {
        $consolidated = [];

        foreach ($rawItems as $item) {
            $key = $item['product_id'] . '::' . ($item['presentation_id'] ?? 'none');

            if (isset($consolidated[$key])) {
                $existing = &$consolidated[$key];
                $existing['cantidad_presentaciones'] += $item['cantidad_presentaciones'];
                $existing['cantidad']                += $item['cantidad'];
                $existing['subtotal']                += $item['subtotal'];
            } else {
                $consolidated[$key] = $item;
            }
        }

        ksort($consolidated);
        return array_values($consolidated);
    }
}
