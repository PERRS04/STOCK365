<?php

namespace App\Livewire;

use App\Models\CashSession;
use App\Services\LiveCashBoxService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LiveCashBox extends Component
{
    public function render()
    {
        $user = Auth::user();

        $session = ($user?->sede_id !== null)
            ? CashSession::where('user_id', $user->id)
                ->where('sede_id', $user->sede_id)
                ->whereIn('status', ['open', 'pending_closing'])
                ->first()
            : null;

        $snapshot = $session ? app(LiveCashBoxService::class)->snapshot($session) : null;

        return view('livewire.live-cash-box', [
            'session'  => $session,
            'snapshot' => $snapshot,
        ]);
    }
}
