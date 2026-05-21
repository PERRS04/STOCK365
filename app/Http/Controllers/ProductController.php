<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->can('products.view'), 403);

        return view('admin.products.index');
    }

    public function create()
    {
        abort_unless(auth()->user()->can('products.create'), 403);

        return view('admin.products.create');
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('products.create'), 403);

        $validated = $request->validate([
            'sku'           => 'required|string|unique:products',
            'nombre'        => 'required|string|max:255',
            'marca'         => 'required|string|max:255',
            'tamaño'        => 'required|string|max:100',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta'  => 'required|numeric|min:0',
            'stock_minimo'  => 'required|integer|min:0',
            'descripcion'   => 'nullable|string',
            'imagen'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('imagen')) {
            $validated['imagen'] = $request->file('imagen')->store('products', 'public');
        }

        $validated['created_by'] = Auth::id();
        $validated['activo']     = true;

        $product = Product::create($validated);

        ActivityLogger::log(
            'product.create',
            "Producto creado: {$product->nombre} (SKU: {$product->sku})",
            $product,
            [],
            $product->only(['sku', 'nombre', 'precio_compra', 'precio_venta'])
        );

        return redirect()->route('products.index')
            ->with('success', 'Producto creado exitosamente');
    }

    public function edit(Product $product)
    {
        abort_unless(auth()->user()->can('products.edit'), 403);

        return view('admin.products.edit', ['product' => $product]);
    }

    public function update(Request $request, Product $product)
    {
        abort_unless(auth()->user()->can('products.edit'), 403);

        $validated = $request->validate([
            'nombre'        => 'required|string|max:255',
            'marca'         => 'required|string|max:255',
            'tamaño'        => 'required|string|max:100',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta'  => 'required|numeric|min:0',
            'stock_minimo'  => 'required|integer|min:0',
            'descripcion'   => 'nullable|string',
            'imagen'        => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('imagen')) {
            $validated['imagen'] = $request->file('imagen')->store('products', 'public');
        }

        $old = $product->only(['nombre', 'precio_compra', 'precio_venta']);
        $product->update($validated);

        ActivityLogger::log(
            'product.edit',
            "Producto editado: {$product->nombre}",
            $product,
            $old,
            $product->fresh()->only(['nombre', 'precio_compra', 'precio_venta'])
        );

        return redirect()->route('products.index')
            ->with('success', 'Producto actualizado exitosamente');
    }

    public function destroy(Product $product)
    {
        abort_unless(auth()->user()->can('products.delete'), 403);

        ActivityLogger::log(
            'product.delete',
            "Producto desactivado: {$product->nombre} (SKU: {$product->sku})",
            $product,
            $product->only(['nombre', 'sku', 'activo']),
            ['activo' => false]
        );

        $product->update(['activo' => false]);
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Producto desactivado');
    }
}
