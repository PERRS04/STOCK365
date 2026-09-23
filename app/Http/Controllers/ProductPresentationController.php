<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\PresentationSedePrice;
use App\Models\ProductSedePrice;
use App\Models\SaleItem;
use App\Models\Sede;
use Illuminate\Http\Request;

class ProductPresentationController extends Controller
{
    // ── Boss-only gate applied in every method ────────────────────────────────

    /**
     * Show all presentations and sede prices for a product.
     */
    public function index(Product $product)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        $product->load(['presentations.sedePrices.sede', 'sedePrices.sede']);
        $sedes = Sede::where('activa', true)->orderBy('nombre')->get();

        return view('admin.presentations.index', compact('product', 'sedes'));
    }

    /**
     * Create a new presentation for a product.
     */
    public function storePresentation(Request $request, Product $product)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        $data = $request->validate([
            'nombre'      => 'required|string|max:100',
            'factor_stock' => 'required|integer|min:1',
            'sort_order'   => 'nullable|integer|min:0',
        ]);

        $product->presentations()->create([
            'nombre'      => $data['nombre'],
            'factor_stock' => $data['factor_stock'],
            'sort_order'   => $data['sort_order'] ?? 0,
            'activo'       => true,
        ]);

        return redirect()->route('product-pricing.index', $product)
            ->with('success', 'Presentación creada.');
    }

    /**
     * Update a presentation.
     * Blocks factor_stock change if sales exist for this presentation.
     */
    public function updatePresentation(Request $request, Product $product, ProductPresentation $presentation)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        $data = $request->validate([
            'nombre'      => 'required|string|max:100',
            'factor_stock' => 'required|integer|min:1',
            'sort_order'   => 'nullable|integer|min:0',
            'activo'       => 'boolean',
        ]);

        // Guard: factor_stock is immutable once sales reference this presentation
        if ((int) $data['factor_stock'] !== (int) $presentation->factor_stock) {
            if (SaleItem::where('presentation_id', $presentation->id)->exists()) {
                return back()->withErrors([
                    'factor_stock' => 'No se puede modificar el factor de una presentación con ventas históricas.',
                ]);
            }
        }

        $presentation->update([
            'nombre'      => $data['nombre'],
            'factor_stock' => $data['factor_stock'],
            'sort_order'   => $data['sort_order'] ?? $presentation->sort_order,
            'activo'       => $data['activo'] ?? $presentation->activo,
        ]);

        return redirect()->route('product-pricing.index', $product)
            ->with('success', 'Presentación actualizada.');
    }

    /**
     * Delete a presentation.
     * If sales exist: deactivate instead of hard-delete.
     */
    public function destroyPresentation(Product $product, ProductPresentation $presentation)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        if (SaleItem::where('presentation_id', $presentation->id)->exists()) {
            $presentation->update(['activo' => false]);
            return redirect()->route('product-pricing.index', $product)
                ->with('success', 'La presentación tiene ventas históricas y fue desactivada en lugar de eliminada.');
        }

        $presentation->delete();

        return redirect()->route('product-pricing.index', $product)
            ->with('success', 'Presentación eliminada.');
    }

    /**
     * Add or update a sede price for a presentation.
     * sede_id = null means global price.
     */
    public function storePrice(Request $request, Product $product, ProductPresentation $presentation)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        $data = $request->validate([
            'sede_id'      => 'nullable|exists:sedes,id',
            'precio_venta' => 'required|numeric|min:0',
        ]);

        PresentationSedePrice::updateOrCreate(
            [
                'presentation_id' => $presentation->id,
                'sede_id'         => $data['sede_id'] ?? null,
            ],
            [
                'precio_venta' => $data['precio_venta'],
                'activo'       => true,
            ]
        );

        return redirect()->route('product-pricing.index', $product)
            ->with('success', 'Precio de presentación guardado.');
    }

    /**
     * Delete a presentation sede price.
     */
    public function destroyPrice(Product $product, ProductPresentation $presentation, PresentationSedePrice $price)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        $price->delete();

        return redirect()->route('product-pricing.index', $product)
            ->with('success', 'Precio eliminado.');
    }

    /**
     * Add or update a product sede price (for products without presentations).
     */
    public function storeSedePrice(Request $request, Product $product)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        $data = $request->validate([
            'sede_id'      => 'required|exists:sedes,id',
            'precio_venta' => 'required|numeric|min:0',
        ]);

        ProductSedePrice::updateOrCreate(
            [
                'product_id' => $product->id,
                'sede_id'    => $data['sede_id'],
            ],
            [
                'precio_venta' => $data['precio_venta'],
                'activo'       => true,
            ]
        );

        return redirect()->route('product-pricing.index', $product)
            ->with('success', 'Precio por sede guardado.');
    }

    /**
     * Delete a product sede price.
     */
    public function destroySedePrice(Product $product, ProductSedePrice $price)
    {
        abort_unless(auth()->user()->isBoss(), 403);

        $price->delete();

        return redirect()->route('product-pricing.index', $product)
            ->with('success', 'Precio por sede eliminado.');
    }
}
