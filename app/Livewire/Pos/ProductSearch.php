<?php

namespace App\Livewire\Pos;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ProductSearch extends Component
{
    public string $search = '';

    #[Computed]
    public function products()
    {
        $products = Product::where('activo', true)
            ->when($this->search, function ($q) {
                $q->where(function ($inner) {
                    $inner->where('nombre', 'like', "%{$this->search}%")
                          ->orWhere('sku', 'like', "%{$this->search}%")
                          ->orWhere('marca', 'like', "%{$this->search}%");
                });
            })
            ->orderBy('nombre')
            ->limit(48)
            ->get();

        $sedeId = Auth::user()?->sede_id;

        if ($sedeId) {
            $stockMap = Inventory::where('sede_id', $sedeId)
                ->pluck('cantidad_stock', 'product_id');

            $products->each(function ($product) use ($stockMap) {
                $product->stock_sede = $stockMap->get($product->id, 0);
            });
        } else {
            $products->each(fn($p) => $p->stock_sede = null);
        }

        return $products;
    }

    // Called when the operator presses Enter in the search box.
    // If there is exactly one match, add it to the cart automatically.
    public function selectFirst(): void
    {
        $first = $this->products->first();
        if (!$first) {
            return;
        }

        $this->dispatch('add-to-cart',
            id:     $first->id,
            nombre: $first->nombre,
            precio: (float) $first->precio_venta,
        );

        $this->search = '';
    }

    public function render()
    {
        return view('livewire.pos.product-search');
    }
}
