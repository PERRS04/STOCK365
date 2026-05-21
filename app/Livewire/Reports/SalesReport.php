<?php

namespace App\Livewire\Reports;

use App\Models\Sale;
use App\Models\Sede;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SalesReport extends Component
{
    use WithPagination;

    #[Url]
    public string $startDate = '';

    #[Url]
    public string $endDate = '';

    #[Url]
    public string $sedeId = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('reports.view'), 403);
    }

    public function updatedStartDate(): void { $this->resetPage(); }
    public function updatedEndDate(): void   { $this->resetPage(); }
    public function updatedSedeId(): void    { $this->resetPage(); }

    public function clear(): void
    {
        $this->startDate = '';
        $this->endDate   = '';
        $this->sedeId    = '';
        $this->resetPage();
    }

    #[Computed]
    public function sedes()
    {
        return Sede::where('activa', true)->get();
    }

    #[Computed]
    public function sales()
    {
        $query = Sale::query();

        if ($this->startDate && $this->endDate) {
            $query->whereBetween('fecha_venta', [
                $this->startDate . ' 00:00:00',
                $this->endDate   . ' 23:59:59',
            ]);
        }

        if ($this->sedeId) {
            $query->where('sede_id', $this->sedeId);
        }

        return $query->with('sede', 'items')
            ->latest('fecha_venta')
            ->paginate(30);
    }

    #[Computed]
    public function totals()
    {
        $row = Sale::query()
            ->when($this->startDate && $this->endDate, fn ($q) => $q->whereBetween('fecha_venta', [
                $this->startDate . ' 00:00:00',
                $this->endDate   . ' 23:59:59',
            ]))
            ->when($this->sedeId, fn ($q) => $q->where('sede_id', $this->sedeId))
            ->selectRaw('SUM(total_sistema) as total, COUNT(*) as count')
            ->first();

        $count = (int) ($row->count ?? 0);
        $total = (float) ($row->total ?? 0);

        return [
            'total'    => $total,
            'count'    => $count,
            'promedio' => $count > 0 ? $total / $count : 0,
        ];
    }

    public function render()
    {
        return view('livewire.reports.sales-report');
    }
}
