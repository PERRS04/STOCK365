<?php

namespace App\Http\Controllers;

use App\Models\InventoryReceipt;
use App\Models\Provider;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('inventory.view'), 403);

        $query = Provider::withCount(['receipts as compras_count' => fn($q) => $q->where('estado', 'aprobado')])
            ->withSum(['receipts as total_comprado' => fn($q) => $q->where('estado', 'aprobado')], 'monto_pagado');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(fn($qb) => $qb->where('nombre', 'like', "%$q%")
                ->orWhere('ruc_nit', 'like', "%$q%")
                ->orWhere('email', 'like', "%$q%"));
        }

        if ($request->get('estado') === 'inactivo') {
            $query->where('activo', false);
        } else {
            $query->where('activo', true);
        }

        $suppliers = $query->orderBy('nombre')->paginate(20)->withQueryString();
        $totalActive   = Provider::where('activo', true)->count();
        $totalInactive = Provider::where('activo', false)->count();

        return view('admin.suppliers.index', compact('suppliers', 'totalActive', 'totalInactive'));
    }

    public function create()
    {
        abort_unless(auth()->user()->can('products.create'), 403);
        return view('admin.suppliers.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('products.create'), 403);

        $validated = $request->validate([
            'nombre'             => 'required|string|max:255',
            'ruc_nit'            => 'nullable|string|max:50|unique:providers,ruc_nit',
            'email'              => 'nullable|email|max:255',
            'telefono'           => 'nullable|string|max:50',
            'contacto_principal' => 'nullable|string|max:255',
            'direccion'          => 'nullable|string|max:500',
            'observaciones'      => 'nullable|string|max:1000',
        ]);

        $supplier = Provider::create(array_merge($validated, [
            'activo'     => true,
            'created_by' => auth()->id(),
        ]));

        ActivityLogger::log('proveedor.creado', "Proveedor creado: {$supplier->nombre}", $supplier);

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Proveedor creado correctamente.');
    }

    public function show(Provider $supplier)
    {
        abort_unless(auth()->user()->can('inventory.view'), 403);

        $supplier->load('createdBy');

        $receipts = InventoryReceipt::with('sede', 'user')
            ->where('provider_id', $supplier->id)
            ->latest()
            ->paginate(15);

        $stats = [
            'total_compras'   => InventoryReceipt::where('provider_id', $supplier->id)->where('estado', 'aprobado')->count(),
            'total_pagado'    => InventoryReceipt::where('provider_id', $supplier->id)->where('estado', 'aprobado')->sum('monto_pagado'),
            'ultima_compra'   => InventoryReceipt::where('provider_id', $supplier->id)->where('estado', 'aprobado')->latest()->value('created_at'),
            'pendientes'      => InventoryReceipt::where('provider_id', $supplier->id)->where('estado', 'pendiente')->count(),
        ];

        return view('admin.suppliers.show', compact('supplier', 'receipts', 'stats'));
    }

    public function edit(Provider $supplier)
    {
        abort_unless(auth()->user()->can('products.create'), 403);
        return view('admin.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Provider $supplier)
    {
        abort_unless(auth()->user()->can('products.create'), 403);

        $validated = $request->validate([
            'nombre'             => 'required|string|max:255',
            'ruc_nit'            => 'nullable|string|max:50|unique:providers,ruc_nit,' . $supplier->id,
            'email'              => 'nullable|email|max:255',
            'telefono'           => 'nullable|string|max:50',
            'contacto_principal' => 'nullable|string|max:255',
            'direccion'          => 'nullable|string|max:500',
            'observaciones'      => 'nullable|string|max:1000',
        ]);

        $supplier->update($validated);
        ActivityLogger::log('proveedor.editado', "Proveedor editado: {$supplier->nombre}", $supplier);

        return redirect()->route('suppliers.show', $supplier)->with('success', 'Proveedor actualizado.');
    }

    public function destroy(Provider $supplier)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        $supplier->update(['activo' => false]);
        ActivityLogger::log('proveedor.desactivado', "Proveedor desactivado: {$supplier->nombre}", $supplier);

        return redirect()->route('suppliers.index')->with('warning', 'Proveedor desactivado.');
    }
}
