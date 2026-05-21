<?php

namespace App\Livewire\Admin;

use App\Models\ActivityLog;
use App\Models\Sede;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ActivityLogTable extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $action = '';

    #[Url]
    public string $role = '';

    #[Url]
    public string $sedeId = '';

    #[Url]
    public string $startDate = '';

    #[Url]
    public string $endDate = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('reports.view'), 403);
    }

    public function updatedSearch(): void   { $this->resetPage(); }
    public function updatedAction(): void   { $this->resetPage(); }
    public function updatedRole(): void     { $this->resetPage(); }
    public function updatedSedeId(): void   { $this->resetPage(); }
    public function updatedStartDate(): void { $this->resetPage(); }
    public function updatedEndDate(): void  { $this->resetPage(); }

    public function clear(): void
    {
        $this->search    = '';
        $this->action    = '';
        $this->role      = '';
        $this->sedeId    = '';
        $this->startDate = '';
        $this->endDate   = '';
        $this->resetPage();
    }

    #[Computed]
    public function sedes()
    {
        return Sede::where('activa', true)->orderBy('nombre')->get();
    }

    #[Computed]
    public function logs()
    {
        return ActivityLog::with('user', 'sede')
            ->when($this->search, fn ($q) =>
                $q->where(function ($inner) {
                    $inner->where('user_name', 'like', "%{$this->search}%")
                          ->orWhere('description', 'like', "%{$this->search}%")
                          ->orWhere('ip_address', 'like', "%{$this->search}%");
                })
            )
            ->when($this->action, fn ($q) => $q->where('action', 'like', "{$this->action}%"))
            ->when($this->role,   fn ($q) => $q->where('user_role', $this->role))
            ->when($this->sedeId, fn ($q) => $q->where('sede_id', $this->sedeId))
            ->when($this->startDate, fn ($q) => $q->whereDate('created_at', '>=', $this->startDate))
            ->when($this->endDate,   fn ($q) => $q->whereDate('created_at', '<=', $this->endDate))
            ->latest()
            ->paginate(30);
    }

    #[Computed]
    public function actionGroups(): array
    {
        return [
            'sale'      => 'Ventas',
            'product'   => 'Productos',
            'inventory' => 'Inventario',
            'closing'   => 'Cierres',
            'user'      => 'Usuarios',
        ];
    }

    public function render()
    {
        return view('livewire.admin.activity-log-table');
    }
}
