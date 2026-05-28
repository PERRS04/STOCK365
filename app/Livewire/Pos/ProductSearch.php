<?php

namespace App\Livewire\Pos;

use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ProductSearch extends Component
{
    public string $search = '';
    public string $marca  = '';

    #[Computed]
    public function marcas(): Collection
    {
        return Product::where('activo', true)
            ->whereNotNull('marca')
            ->where('marca', '!=', '')
            ->distinct()
            ->orderBy('marca')
            ->pluck('marca');
    }

    #[Computed]
    public function products(): Collection
    {
        $sedeId = Auth::user()?->sede_id;

        $query = Product::where('activo', true)
            ->when($this->search, fn ($q) =>
                $q->where(fn ($inner) =>
                    $inner->where('nombre', 'like', "%{$this->search}%")
                          ->orWhere('sku',   'like', "%{$this->search}%")
                          ->orWhere('marca', 'like', "%{$this->search}%")
                )
            )
            ->when($this->marca, fn ($q) => $q->where('marca', $this->marca))
            ->orderBy('nombre')
            ->limit(60)
            ->get();

        if ($sedeId) {
            $stockMap = Inventory::where('sede_id', $sedeId)
                ->whereIn('product_id', $query->pluck('id'))
                ->pluck('cantidad_stock', 'product_id');

            $query->each(fn ($p) => $p->stock_sede = $stockMap->get($p->id, 0));
        } else {
            $query->each(fn ($p) => $p->stock_sede = null);
        }

        return $query;
    }

    public function selectFirst(): void
    {
        $first = $this->products->first();
        if (!$first) return;
        if (($first->stock_sede ?? 1) === 0) return;

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
