<?php

namespace App\Livewire;

use App\Models\CashSession;
use App\Services\LiveCashBoxService;
use Livewire\Component;

class LiveCashBox extends Component
{
    public function render()
    {
        $user    = auth()->user();
        $session = ($user->sede_id !== null)
            ? CashSession::activeForUser($user->id, $user->sede_id)
            : null;

        $snapshot = null;
        if ($session) {
            $snapshot = app(LiveCashBoxService::class)->snapshot($session);
        }

        return view('livewire.live-cash-box', [
            'session'  => $session,
            'snapshot' => $snapshot,
        ]);
    }
}
