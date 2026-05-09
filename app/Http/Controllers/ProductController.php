<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index()
    {
        $this->authorize('boss');

        $products = Product::where('activo', true)
            ->with('createdBy')
            ->paginate(15);

        return view('admin.products.index', ['products' => $products]);
    }

    public function create()
    {
        $this->authorize('boss');
        return view('admin.products.create');
    }

    public function store(Request $request)
    {
        $this->authorize('boss');

        $validated = $request->validate([
            'sku' => 'required|string|unique:products',
            'nombre' => 'required|string|max:255',
            'marca' => 'required|string|max:255',
            'tamaño' => 'required|string|max:100',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'descripcion' => 'nullable|string',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('imagen')) {
            $validated['imagen'] = $request->file('imagen')->store('products', 'public');
        }

        $validated['created_by'] = Auth::id();
        $validated['activo'] = true;

        Product::create($validated);

        return redirect()->route('products.index')
            ->with('success', 'Producto creado exitosamente');
    }

    public function edit(Product $product)
    {
        $this->authorize('boss');
        return view('admin.products.edit', ['product' => $product]);
    }

    public function update(Request $request, Product $product)
    {
        $this->authorize('boss');

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'marca' => 'required|string|max:255',
            'tamaño' => 'required|string|max:100',
            'precio_compra' => 'required|numeric|min:0',
            'precio_venta' => 'required|numeric|min:0',
            'stock_minimo' => 'required|integer|min:0',
            'descripcion' => 'nullable|string',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('imagen')) {
            $validated['imagen'] = $request->file('imagen')->store('products', 'public');
        }

        $product->update($validated);

        return redirect()->route('products.index')
            ->with('success', 'Producto actualizado exitosamente');
    }

    public function destroy(Product $product)
    {
        $this->authorize('boss');
        $product->update(['activo' => false]);

        return redirect()->route('products.index')
            ->with('success', 'Producto desactivado');
    }
}
