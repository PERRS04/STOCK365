<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sede;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AlmacenWorkspaceController extends Controller
{
    private function guard(): void
    {
        abort_unless(auth()->user()->isOperator(), 403);
        abort_if(auth()->user()->almacen_id === null, 403);
    }

    public function stock()
    {
        $this->guard();

        $almacen = auth()->user()->almacen;

        $inventories = Inventory::where('almacen_id', $almacen->id)
            ->with('product')
            ->orderByDesc('cantidad_stock')
            ->paginate(30);

        $totalStock     = Inventory::where('almacen_id', $almacen->id)->sum('cantidad_stock');
        $totalProductos = Inventory::where('almacen_id', $almacen->id)->count();

        return view('deposito.stock', compact('almacen', 'inventories', 'totalStock', 'totalProductos'));
    }

    public function movements()
    {
        $this->guard();

        $almacen   = auth()->user()->almacen;
        $movements = InventoryMovement::where('almacen_id', $almacen->id)
            ->with('product', 'user')
            ->latest('fecha_movimiento')
            ->paginate(30);

        return view('deposito.movimientos', compact('almacen', 'movements'));
    }

    public function transfers(Request $request)
    {
        $this->guard();

        $almacenId = auth()->user()->almacen_id;
        $almacen   = auth()->user()->almacen;

        $query = StockTransfer::where(function ($q) use ($almacenId) {
            $q->where('from_almacen_id', $almacenId)
              ->orWhere('to_almacen_id', $almacenId);
        })->with('fromSede', 'toSede', 'fromAlmacen', 'toAlmacen', 'createdBy');

        $validStates = ['pendiente', 'aprobado', 'rechazado'];
        $estadoFilter = $request->get('estado');
        if ($estadoFilter && in_array($estadoFilter, $validStates)) {
            $query->where('estado', $estadoFilter);
        }

        $transfers = $query->latest()->paginate(20)->withQueryString();

        $pendingCount = StockTransfer::where('estado', 'pendiente')
            ->where(function ($q) use ($almacenId) {
                $q->where('from_almacen_id', $almacenId)
                  ->orWhere('to_almacen_id', $almacenId);
            })->count();

        return view('deposito.transferencias', compact('almacen', 'transfers', 'pendingCount'));
    }

    public function showTransfer(StockTransfer $transfer)
    {
        $this->guard();

        $almacenId = auth()->user()->almacen_id;

        // Operator may only see transfers that involve their warehouse
        abort_unless(
            $transfer->from_almacen_id === $almacenId || $transfer->to_almacen_id === $almacenId,
            403
        );

        $transfer->load('fromSede', 'toSede', 'fromAlmacen', 'toAlmacen', 'createdBy', 'approvedBy', 'items.product');

        return view('deposito.show-transfer', compact('transfer'));
    }

    public function createTransfer()
    {
        $this->guard();

        $almacen   = auth()->user()->almacen;
        $sedes     = Sede::where('activa', true)->orderBy('nombre')->get();
        $almacenes = Almacen::where('activo', true)
            ->where('id', '!=', $almacen->id)
            ->orderBy('nombre')
            ->get();
        $products  = Product::where('activo', true)->orderBy('nombre')->get();

        return view('deposito.create-transfer', compact('almacen', 'sedes', 'almacenes', 'products'));
    }

    public function storeTransfer(Request $request)
    {
        $this->guard();

        $user      = auth()->user();
        $almacen   = $user->almacen;

        $validated = $request->validate([
            'to_sede_id'         => ['nullable', Rule::exists('sedes', 'id')->where('activa', true)],
            'to_almacen_id'      => ['nullable', Rule::exists('almacenes', 'id')->where('activo', true)],
            'motivo'             => 'nullable|string|max:500',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.cantidad'   => 'required|integer|min:1',
        ]);

        // Origin is always the operator's own warehouse — never from request
        $fromAlmacenId = $user->almacen_id;
        $fromSedeId    = null;

        $toSedeId    = (int) ($validated['to_sede_id']    ?? 0) ?: null;
        $toAlmacenId = (int) ($validated['to_almacen_id'] ?? 0) ?: null;

        // XOR: exactly one destination
        if (($toSedeId !== null) === ($toAlmacenId !== null)) {
            return back()->withInput()->withErrors(
                ['to' => 'Selecciona exactamente un destino: Sede o Almacén.']
            );
        }

        // Cannot transfer to self
        if ($toAlmacenId !== null && $toAlmacenId === $fromAlmacenId) {
            return back()->withInput()->withErrors(
                ['to_almacen_id' => 'No puedes transferir al mismo depósito de origen.']
            );
        }

        // Duplicate product guard
        $productIds = collect($validated['items'])->pluck('product_id');
        if ($productIds->unique()->count() !== $productIds->count()) {
            return back()->withInput()->with('error', 'No puedes incluir el mismo producto más de una vez.');
        }

        // Pre-check stock at origin (UX hint — authoritative check at approval time)
        foreach ($validated['items'] as $item) {
            $stock = Inventory::where('product_id', $item['product_id'])
                ->whereNull('sede_id')
                ->where('almacen_id', $fromAlmacenId)
                ->value('cantidad_stock') ?? 0;

            if ($stock < $item['cantidad']) {
                $product = Product::find($item['product_id']);
                return back()->withInput()->with('error',
                    "Stock insuficiente para \"{$product->nombre}\". Disponible: {$stock}.");
            }
        }

        DB::transaction(function () use ($validated, $fromAlmacenId, $toSedeId, $toAlmacenId) {
            $transfer = StockTransfer::create([
                'from_sede_id'    => null,
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

            $auditNewValues = [
                'estado'            => 'pendiente',
                'origen_almacen_id' => $fromAlmacenId,
                'destino_sede_id'   => $toSedeId,
                'destino_almacen_id'=> $toAlmacenId,
            ];

            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                $label   = ($product?->nombre ?? "Producto #{$item['product_id']}")
                    . " (#{$item['product_id']})";
                $auditNewValues[$label] = (int) $item['cantidad'];
            }

            ActivityLogger::log(
                'transferencia.creada',
                "Transferencia #{$transfer->id} creada: {$transfer->fromLocationName()} → {$transfer->toLocationName()}",
                $transfer,
                [],
                $auditNewValues,
                null,
                $fromAlmacenId,
            );
        });

        return redirect()->route('deposito.transferencias')
            ->with('success', 'Transferencia solicitada. Pendiente de aprobación por supervisor o boss.');
    }
}
