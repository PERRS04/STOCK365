<?php

namespace App\Livewire;

use App\Models\CashClosing;
use App\Models\CashSession;
use App\Models\Sale;
use App\Models\Sede;
use App\Models\StockAlert;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class LiveKpiStrip extends Component
{
    public float $totalSalesToday     = 0;
    public float $totalSalesMonth     = 0;
    public int   $transactionCount    = 0;
    public float $avgTicket           = 0;
    public float $utilidadHoy         = 0;
    public int   $pendingClosings     = 0;
    public int   $alertCount          = 0;
    public int   $activeSessionsCount = 0;
    public bool  $isBoss              = false;

    public function mount(): void
    {
        $this->isBoss = Auth::user()?->isBoss() ?? false;
    }

    public function render()
    {
        abort_unless(Auth::user()?->isAdminLevel(), 403);
        $this->refresh();
        return view('livewire.live-kpi-strip');
    }

    private function refresh(): void
    {
        $today   = now()->toDateString();
        $demoIds = Sede::demoIds();
        $isDemo  = str_starts_with(Auth::user()->email ?? '', 'demo-')
                   || (bool) Auth::user()->sede?->is_demo;

        $salesToday = Sale::whereDate('fecha_venta', $today)
            ->where('estado', 'completada')
            ->when($isDemo, fn($q) => $q->whereIn('sede_id', $demoIds),
                            fn($q) => $q->whereNotIn('sede_id', $demoIds))
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(total_sistema),0) as total')
            ->first();

        $this->transactionCount = (int)   ($salesToday->cnt   ?? 0);
        $this->totalSalesToday  = (float) ($salesToday->total ?? 0);
        $this->avgTicket        = $this->transactionCount > 0
            ? $this->totalSalesToday / $this->transactionCount
            : 0;

        $this->totalSalesMonth = (float) Sale::whereMonth('fecha_venta', now()->month)
            ->whereYear('fecha_venta', now()->year)
            ->where('estado', 'completada')
            ->when($isDemo, fn($q) => $q->whereIn('sede_id', $demoIds),
                            fn($q) => $q->whereNotIn('sede_id', $demoIds))
            ->sum('total_sistema');

        if ($this->isBoss) {
            $this->utilidadHoy = (float) DB::table('sale_items')
                ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
                ->join('products', 'products.id', '=', 'sale_items.product_id')
                ->whereDate('sales.fecha_venta', $today)
                ->where('sales.estado', 'completada')
                ->when($isDemo, fn($q) => $q->whereIn('sales.sede_id', $demoIds),
                                fn($q) => $q->whereNotIn('sales.sede_id', $demoIds))
                ->sum(DB::raw('sale_items.cantidad * (products.precio_venta - products.precio_compra)'));
        }

        $this->pendingClosings = CashClosing::where('estado', 'pendiente')
            ->when($isDemo, fn($q) => $q->whereIn('sede_id', $demoIds),
                            fn($q) => $q->whereNotIn('sede_id', $demoIds))
            ->count();

        $this->alertCount = StockAlert::where('alerta_activa', true)
            ->when($isDemo, fn($q) => $q->whereIn('sede_id', $demoIds),
                            fn($q) => $q->whereNotIn('sede_id', $demoIds))
            ->count();

        $this->activeSessionsCount = CashSession::whereIn('status', ['open', 'pending_closing'])
            ->when($isDemo, fn($q) => $q->whereIn('sede_id', $demoIds),
                            fn($q) => $q->whereNotIn('sede_id', $demoIds))
            ->count();
    }
}
