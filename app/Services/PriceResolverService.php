<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPresentation;
use App\Models\PresentationSedePrice;
use App\Models\ProductSedePrice;
use Illuminate\Support\Collection;

class PriceResolverService
{
    /**
     * Resolve the price for a presentation at a sede.
     * Returns null if the presentation has no price configured for this sede
     * (meaning it cannot be sold there).
     */
    public function forPresentation(ProductPresentation $presentation, int $sedeId): ?float
    {
        // 1. Sede-specific price
        $price = PresentationSedePrice::where('presentation_id', $presentation->id)
            ->where('sede_id', $sedeId)
            ->where('activo', true)
            ->value('precio_venta');

        if ($price !== null) {
            return (float) $price;
        }

        // 2. Global price (sede_id IS NULL)
        $global = PresentationSedePrice::where('presentation_id', $presentation->id)
            ->whereNull('sede_id')
            ->where('activo', true)
            ->value('precio_venta');

        if ($global !== null) {
            return (float) $global;
        }

        // 3. No price configured — presentation not available for this sede
        return null;
    }

    /**
     * Resolve the price for a product at a sede (for products without presentations).
     * Always returns a value (fallback to Product.precio_venta).
     */
    public function forProduct(Product $product, int $sedeId): float
    {
        $price = ProductSedePrice::where('product_id', $product->id)
            ->where('sede_id', $sedeId)
            ->where('activo', true)
            ->value('precio_venta');

        return $price !== null ? (float) $price : (float) $product->precio_venta;
    }

    /**
     * Get active presentations for a product at a sede, with resolved prices.
     * Only includes presentations that have a valid price for this sede.
     */
    public function presentationsForSede(Product $product, int $sedeId): Collection
    {
        return $product->presentations()
            ->where('activo', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($presentation) use ($sedeId) {
                $price = $this->forPresentation($presentation, $sedeId);
                if ($price === null) {
                    return null;
                }
                return [
                    'id'     => $presentation->id,
                    'nombre' => $presentation->nombre,
                    'factor' => $presentation->factor_stock,
                    'precio' => $price,
                ];
            })
            ->filter()
            ->values();
    }
}
