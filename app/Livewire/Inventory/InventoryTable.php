<?php

namespace App\Livewire\Inventory;

use App\Models\Inventory;
use App\Models\Sede;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryTable extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public ?int $sedeId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('inventory.view'), 403);
        if (!$this->sedeId) {
            $this->sedeId = Sede::where('activa', true)->orderBy('nombre')->value('id');
        }
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSedeId(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function sedes()
    {
        return Sede::where('activa', true)->get();
    }

    #[Computed]
    public function inventories()
    {
        return Inventory::where('sede_id', $this->sedeId)
            ->whereHas('product')
            ->when($this->search, function ($q) {
                $q->whereHas('product', function ($inner) {
                    $inner->where('nombre', 'like', "%{$this->search}%")
                          ->orWhere('sku', 'like', "%{$this->search}%");
                });
            })
            ->with('product')
            ->paginate(20);
    }

    public function render()
    {
        return view('livewire.inventory.inventory-table');
    }
}
