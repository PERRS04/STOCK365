<?php

namespace App\Livewire;

use App\Enums\CashRiskLevel;
use App\Models\CashSession;
use App\Services\LiveCashEngine\LiveCashEngineService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LiveCashBox extends Component
{
    // ── Public props observed by Alpine $wire.$watch() ────────────────────────
    // Flat scalar properties — Livewire serializes these to JS after each poll,
    // allowing Alpine to detect changes and run animated transitions.

    public float  $totalInBox    = 0;
    public float  $ventas        = 0;
    public float  $egresos       = 0;
    public float  $ingresos      = 0;
    public float  $cortesias     = 0;
    public float  $opening       = 0;
    public int    $pendingMov    = 0;
    public int    $pendingCor    = 0;
    public string $cashStatus    = 'none';  // 'ok' | 'baja' | 'negativa' | 'none'
    public string $riskLevel     = 'normal';
    public bool   $hasSession    = false;
    public string $sessionStatus = 'open';  // 'open' | 'pending_closing'
    public string $duration      = '00:00';
    public string $openedAtFmt   = '';

    public function render()
    {
        $user = Auth::user();

        $session = $user?->sede_id
            ? CashSession::where('user_id', $user->id)
                ->where('sede_id', $user->sede_id)
                ->whereIn('status', ['open', 'pending_closing'])
                ->first()
            : null;

        if (!$session) {
            $this->hasSession = false;
            $this->cashStatus = 'none';
            $this->riskLevel  = 'normal';
            return view('livewire.live-cash-box', ['session' => null, 'snapshot' => null, 'risk' => null]);
        }

        $engine   = app(LiveCashEngineService::class);
        $snapshot = $engine->snapshot($session);
        $risk     = $engine->risk($session, $snapshot);

        // Sync public props so Alpine watchers fire on changes
        $this->totalInBox    = (float)  $snapshot['total'];
        $this->ventas        = (float)  $snapshot['ventas_efectivo'];
        $this->egresos       = (float)  $snapshot['egresos'];
        $this->ingresos      = (float)  $snapshot['ingresos'];
        $this->cortesias     = (float)  $snapshot['valor_cortesias'];
        $this->opening       = (float)  $snapshot['opening'];
        $this->pendingMov    = (int)    $snapshot['pending_mov'];
        $this->pendingCor    = (int)    $snapshot['pending_cor'];
        $this->cashStatus    = (string) $snapshot['status'];
        $this->riskLevel     = $risk->value;
        $this->hasSession    = true;
        $this->sessionStatus = $session->status;
        $this->duration      = $session->duration;
        $this->openedAtFmt   = $session->opened_at->format('H:i');

        return view('livewire.live-cash-box', compact('session', 'snapshot', 'risk'));
    }
}
