<?php

namespace App\Livewire;

use App\Enums\OperationalStatus;
use App\Models\CashSession;
use App\Models\Sede;
use App\Models\StockAlert;
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
        $sedes = Sede::where('activa', true)->orderBy('nombre')->get();

        // Load active sessions with user info — one query
        $sessions = CashSession::whereIn('status', ['open', 'pending_closing'])
            ->with('user:id,name,sede_id')
            ->get()
            ->groupBy('sede_id');

        // Stock alerts per sede — one query
        $stockCounts = StockAlert::where('alerta_activa', true)
            ->selectRaw('sede_id, count(*) as total')
            ->groupBy('sede_id')
            ->pluck('total', 'sede_id');

        return $sedes->map(function (Sede $sede) use ($sessions, $stockCounts) {
            $sedeSessions   = $sessions->get($sede->id, collect());
            $openSessions   = $sedeSessions->where('status', 'open');
            $pendingSessions = $sedeSessions->where('status', 'pending_closing');
            $stockAlerts    = (int) ($stockCounts[$sede->id] ?? 0);

            $health = $this->computeHealth($openSessions, $pendingSessions, $stockAlerts);

            $operators = $sedeSessions
                ->pluck('user.name')
                ->filter()
                ->unique()
                ->values();

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
        // CRITICAL: any open session abandoned (not opened today or open > 16h)
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

        return OperationalStatus::LOCKED; // reused as OFFLINE — no active sessions
    }
}
