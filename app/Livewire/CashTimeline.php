<?php

namespace App\Livewire;

use App\Models\CashSession;
use App\Services\LiveCashEngine\LiveCashEngineService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CashTimeline extends Component
{
    public function render()
    {
        $user = Auth::user();

        if (!$user?->sede_id) {
            return view('livewire.cash-timeline', ['events' => collect(), 'session' => null]);
        }

        $session = CashSession::where('user_id', $user->id)
            ->where('sede_id', $user->sede_id)
            ->whereIn('status', ['open', 'pending_closing'])
            ->first();

        if (!$session) {
            return view('livewire.cash-timeline', ['events' => collect(), 'session' => null]);
        }

        $events = app(LiveCashEngineService::class)->eventStream($session);

        return view('livewire.cash-timeline', compact('events', 'session'));
    }
}
