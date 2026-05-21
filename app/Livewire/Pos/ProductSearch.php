<?php

namespace App\Livewire\Pos;

use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ProductSearch extends Component
{
    public string $search = '';

    #[Computed]
    public function products()
    {
        return Product::where('activo', true)
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
