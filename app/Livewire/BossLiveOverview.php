<?php

namespace App\Livewire;

use App\Models\CashClosing;
use App\Models\CashSession;
use App\Models\Sede;
use App\Services\LiveCashEngine\LiveCashEngineService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BossLiveOverview extends Component
{
    public function render()
    {
        $service  = app(LiveCashEngineService::class);
        $demoIds  = Sede::demoIds();
        $isDemo   = str_starts_with(Auth::user()->email ?? '', 'demo-')
                    || (bool) Auth::user()->sede?->is_demo;

        $activeSessions = CashSession::whereIn('status', ['open', 'pending_closing'])
            ->when($isDemo, fn($q) => $q->whereIn('sede_id', $demoIds),
                            fn($q) => $q->whereNotIn('sede_id', $demoIds))
            ->with('user:id,name', 'sede:id,nombre')
            ->get();

        $sedeData = $activeSessions->map(fn ($session) => [
            'session'  => $session,
            'snapshot' => $service->snapshot($session),
        ]);

        $globalTotal     = $sedeData->sum(fn ($d) => $d['snapshot']['total']);
        $globalSales     = $sedeData->sum(fn ($d) => $d['snapshot']['ventas_efectivo']);

        $pendingClosings = CashClosing::where('estado', 'pendiente')
            ->when($isDemo, fn($q) => $q->whereIn('sede_id', $demoIds),
                            fn($q) => $q->whereNotIn('sede_id', $demoIds))
            ->count();

        $negativeSedes = $sedeData->filter(fn ($d) => $d['snapshot']['status'] === 'negativa')->count();
        $lowSedes      = $sedeData->filter(fn ($d) => $d['snapshot']['status'] === 'baja')->count();

        return view('livewire.boss-live-overview', compact(
            'sedeData', 'globalTotal', 'globalSales', 'pendingClosings', 'negativeSedes', 'lowSedes'
        ));
    }
}
