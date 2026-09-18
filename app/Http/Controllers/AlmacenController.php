<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;

class AlmacenController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->isBoss(), 403);

        $almacenes = Almacen::orderBy('nombre')->paginate(20);

        return view('admin.almacenes.index', compact('almacenes'));
    }

    public function create()
    {
        abort_unless(auth()->user()->isBoss(), 403);

        return view('admin.almacenes.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        $validated = $request->validate([
            'nombre'      => 'required|string|max:255|unique:almacenes,nombre',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        Almacen::create($validated + ['activo' => true]);

        return redirect()->route('almacenes.index')
            ->with('success', 'Almacén creado exitosamente.');
    }

    public function show(Almacen $almacen)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        $inventories = Inventory::query()
            ->where('almacen_id', $almacen->id)
            ->whereHas('product')
            ->with('product')
            ->orderBy('product_id')
            ->paginate(20);

        $totalProductos = Inventory::where('almacen_id', $almacen->id)->count();
        $totalStock     = (int) Inventory::where('almacen_id', $almacen->id)->sum('cantidad_stock');

        return view('admin.almacenes.show', compact('almacen', 'inventories', 'totalProductos', 'totalStock'));
    }

    public function movements(Almacen $almacen)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        $movements = InventoryMovement::query()
            ->where('almacen_id', $almacen->id)
            ->with('product', 'user', 'almacen')
            ->latest('fecha_movimiento')
            ->paginate(30);

        return view('admin.almacenes.movements', compact('almacen', 'movements'));
    }

    public function edit(Almacen $almacen)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        return view('admin.almacenes.edit', compact('almacen'));
    }

    public function update(Request $request, Almacen $almacen)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        $validated = $request->validate([
            'nombre'      => 'required|string|max:255|unique:almacenes,nombre,' . $almacen->id,
            'descripcion' => 'nullable|string|max:1000',
        ]);

        $validated['activo'] = $request->boolean('activo');

        $almacen->update($validated);

        return redirect()->route('almacenes.index')
            ->with('success', 'Almacén actualizado exitosamente.');
    }
}
