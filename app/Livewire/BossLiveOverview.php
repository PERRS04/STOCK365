<?php

namespace App\Livewire;

use App\Models\CashClosing;
use App\Models\CashSession;
use App\Services\LiveCashEngine\LiveCashEngineService;
use Livewire\Component;

class BossLiveOverview extends Component
{
    public function render()
    {
        $service = app(LiveCashEngineService::class);

        $activeSessions = CashSession::whereIn('status', ['open', 'pending_closing'])
            ->with('user:id,name', 'sede:id,nombre')
            ->get();

        $sedeData = $activeSessions->map(fn ($session) => [
            'session'  => $session,
            'snapshot' => $service->snapshot($session),
        ]);

        $globalTotal     = $sedeData->sum(fn ($d) => $d['snapshot']['total']);
        $globalSales     = $sedeData->sum(fn ($d) => $d['snapshot']['ventas_efectivo']);
        $pendingClosings = CashClosing::where('estado', 'pendiente')->count();
        $negativeSedes   = $sedeData->filter(fn ($d) => $d['snapshot']['status'] === 'negativa')->count();
        $lowSedes        = $sedeData->filter(fn ($d) => $d['snapshot']['status'] === 'baja')->count();

        return view('livewire.boss-live-overview', compact(
            'sedeData', 'globalTotal', 'globalSales', 'pendingClosings', 'negativeSedes', 'lowSedes'
        ));
    }
}
