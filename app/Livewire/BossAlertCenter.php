<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\CashSession;
use App\Services\ActivityLogger;
use App\Services\OperationalIntelligence\AlertDetectionService;
use App\Services\Realtime\IncidentLifecycleService;
use App\Services\Realtime\OperationalRealtimeService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Rule;
use Livewire\Component;

class BossAlertCenter extends Component
{
    // ── Force-close modal state ───────────────────────────────────────────────

    public ?int $forceCloseSessionId = null;

    #[Rule('required|string|min:5|max:500')]
    public string $forceCloseMotivo = '';

    // ── Lifecycle ────────────────────────────────────────────────────────────

    public function render()
    {
        abort_unless(Auth::user()?->isAdminLevel(), 403);

        $alerts   = app(AlertDetectionService::class)->detectAll();
        $critical = $alerts->filter(fn ($a) => $a->isCritical());
        $warning  = $alerts->filter(fn ($a) => $a->isWarning());
        $events   = app(OperationalRealtimeService::class)->getLatestEvents(15);

        return view('livewire.boss-alert-center', compact('alerts', 'critical', 'warning', 'events'));
    }

    // ── Force-close flow ──────────────────────────────────────────────────────

    public function promptForceClose(int $sessionId): void
    {
        abort_unless(Auth::user()?->isAdminLevel(), 403);

        $this->forceCloseSessionId = $sessionId;
        $this->forceCloseMotivo    = '';
        $this->resetValidation();
    }

    public function cancelForceClose(): void
    {
        $this->forceCloseSessionId = null;
        $this->forceCloseMotivo    = '';
        $this->resetValidation();
    }

    public function executeForceClose(): void
    {
        abort_unless(Auth::user()?->isAdminLevel(), 403);

        $this->validateOnly('forceCloseMotivo');

        $session = CashSession::with('user:id,name', 'sede:id,nombre')
            ->findOrFail($this->forceCloseSessionId);

        $previousStatus = $session->status;

        $session->update([
            'status'    => 'closed',
            'closed_at' => now(),
            'notes'     => ($session->notes ? $session->notes . "\n" : '')
                . 'Cierre forzado por ' . Auth::user()->name . ': ' . $this->forceCloseMotivo,
        ]);

        ActivityLogger::log(
            'cash_session.force_close',
            'Caja cerrada forzosamente — '
                . ($session->sede?->nombre ?? '—')
                . ' | Operador: ' . ($session->user?->name ?? '—')
                . ' | Motivo: ' . $this->forceCloseMotivo,
            $session,
            ['status' => $previousStatus],
            ['status' => 'closed', 'motivo' => $this->forceCloseMotivo, 'authorized_by' => Auth::user()->name]
        );

        app(AlertDetectionService::class)->flush();
        app(OperationalRealtimeService::class)->flush();

        $this->forceCloseSessionId = null;
        $this->forceCloseMotivo    = '';

        $this->dispatch('toast', message: 'Sesión cerrada forzosamente. Cambio auditado.', type: 'success');
    }

    // ── Incident lifecycle ────────────────────────────────────────────────────

    public function acknowledgeAlert(string $alertId): void
    {
        abort_unless(Auth::user()?->isAdminLevel(), 403);

        app(IncidentLifecycleService::class)->acknowledge($alertId, Auth::user());
        $this->dispatch('toast', message: 'Alerta marcada como vista.', type: 'info');
    }

    public function resolveAlert(string $alertId): void
    {
        abort_unless(Auth::user()?->isAdminLevel(), 403);

        app(IncidentLifecycleService::class)->resolve($alertId, Auth::user());
        app(AlertDetectionService::class)->flush();
        $this->dispatch('toast', message: 'Incidente marcado como resuelto.', type: 'success');
    }

    public function getIncidentStatus(string $alertId): string
    {
        return app(IncidentLifecycleService::class)->getStatus($alertId)->value;
    }
}
