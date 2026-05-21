<?php

namespace App\Livewire\Products;

use App\Models\Product;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductsTable extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('products.view'), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

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
            ->paginate(20);
    }

    public function deactivate(int $productId): void
    {
        abort_unless(auth()->user()->can('products.delete'), 403);

        $product = Product::findOrFail($productId);
        $product->update(['activo' => false]);
        $product->delete();

        \App\Services\ActivityLogger::log(
            'product.delete',
            "Producto desactivado: {$product->nombre} (SKU: {$product->sku})",
            $product,
            ['activo' => true],
            ['activo' => false]
        );

        $name = addslashes($product->nombre);
        $this->js("window.toast('Producto \"{$name}\" desactivado', 'warning')");
    }

    public function render()
    {
        return view('livewire.products.products-table');
    }
}
