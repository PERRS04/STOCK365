<?php

namespace App\Livewire;

use App\Enums\OperationalStatus;
use App\Models\CashSession;
use App\Models\Sede;
use App\Models\StockAlert;
use App\Services\Realtime\OperationalRealtimeService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OperationalStatusMap extends Component
{
    public function render()
    {
        abort_unless(Auth::user()?->isAdminLevel(), 403);

        return view('livewire.operational-status-map', [
            'nodes' => $this->buildNodes(),
        ]);
    }

    private function buildNodes(): Collection
    {
        $demoIds = Sede::demoIds();
        $isDemo  = str_starts_with(Auth::user()->email ?? '', 'demo-')
                   || (bool) Auth::user()->sede?->is_demo;

        $sedes = $isDemo
            ? Sede::where('is_demo', true)->orderBy('nombre')->get()
            : Sede::where('activa', true)->where('is_demo', false)->orderBy('nombre')->get();

        $sessions = CashSession::whereIn('status', ['open', 'pending_closing'])
            ->when($isDemo, fn($q) => $q->whereIn('sede_id', $demoIds),
                            fn($q) => $q->whereNotIn('sede_id', $demoIds))
            ->with('user:id,name,sede_id')
            ->get()
            ->groupBy('sede_id');

        $stockCounts = StockAlert::where('alerta_activa', true)
            ->when($isDemo, fn($q) => $q->whereIn('sede_id', $demoIds),
                            fn($q) => $q->whereNotIn('sede_id', $demoIds))
            ->selectRaw('sede_id, count(*) as total')
            ->groupBy('sede_id')
            ->pluck('total', 'sede_id');

        $presenceBySede = app(OperationalRealtimeService::class)->getPresenceBySede();

        return $sedes->map(function (Sede $sede) use ($sessions, $stockCounts, $presenceBySede) {
            $sedeSessions    = $sessions->get($sede->id, collect());
            $openSessions    = $sedeSessions->where('status', 'open');
            $pendingSessions = $sedeSessions->where('status', 'pending_closing');
            $stockAlerts     = (int) ($stockCounts[$sede->id] ?? 0);

            $health = $this->computeHealth($openSessions, $pendingSessions, $stockAlerts);

            $sessionOperators = $sedeSessions->pluck('user.name')->filter()->unique();
            $presence         = $presenceBySede->get($sede->id, collect());

            $operators = $sessionOperators->map(function (string $name) use ($presence) {
                $p = $presence->firstWhere('name', $name);
                return [
                    'name'   => $name,
                    'status' => $p ? $p['status'] : 'idle',
                ];
            })->values();

            $lastActivity = $sedeSessions->max('opened_at');

            return [
                'id'              => $sede->id,
                'nombre'          => $sede->nombre,
                'ciudad'          => $sede->ciudad,
                'health'          => $health,
                'openSessions'    => $openSessions->count(),
                'pendingClosings' => $pendingSessions->count(),
                'stockAlerts'     => $stockAlerts,
                'operators'       => $operators,
                'lastActivity'    => $lastActivity,
            ];
        });
    }

    private function computeHealth(
        Collection $openSessions,
        Collection $pendingSessions,
        int $stockAlerts
    ): OperationalStatus {
        foreach ($openSessions as $session) {
            if (!$session->opened_at->isToday()
                || $session->opened_at->diffInHours(now()) >= 16) {
                return OperationalStatus::CRITICAL;
            }
        }

        if ($pendingSessions->isNotEmpty() || $stockAlerts > 0) {
            return OperationalStatus::WARNING;
        }

        if ($openSessions->isNotEmpty()) {
            return OperationalStatus::HEALTHY;
        }

        return OperationalStatus::LOCKED;
    }
}
