<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\CashSession;
use App\Services\ActivityLogger;
use App\Services\OperationalIntelligence\AlertDetectionService;
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

        return view('livewire.boss-alert-center', compact('alerts', 'critical', 'warning'));
    }

    // ── Force-close flow ──────────────────────────────────────────────────────

    /** Open the confirmation modal for a given session. */
    public function promptForceClose(int $sessionId): void
    {
        abort_unless(Auth::user()?->isAdminLevel(), 403);

        $this->forceCloseSessionId = $sessionId;
        $this->forceCloseMotivo    = '';
        $this->resetValidation();
    }

    /** Close the modal without acting. */
    public function cancelForceClose(): void
    {
        $this->forceCloseSessionId = null;
        $this->forceCloseMotivo    = '';
        $this->resetValidation();
    }

    /**
     * Execute the force-close with mandatory reason capture.
     * Handles model update + audit log inline so no page reload is needed.
     */
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

        // Flush alert cache so next render reflects the change immediately
        app(AlertDetectionService::class)->flush();

        $this->forceCloseSessionId = null;
        $this->forceCloseMotivo    = '';

        $this->dispatch('toast', message: 'Sesión cerrada forzosamente. Cambio auditado.', type: 'success');
    }
}
