<?php

namespace App\Livewire;

use App\ExecutiveIntelligence\ExecutiveIntelligenceEngine;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ExecutiveControlTower extends Component
{
    public bool $expanded = false;

    public function toggleExpanded(): void
    {
        $this->expanded = !$this->expanded;
    }

    public function refresh(): void
    {
        app(ExecutiveIntelligenceEngine::class)->flush();
    }

    public function render()
    {
        abort_unless(Auth::user()?->isBoss(), 403);

        $snap = app(ExecutiveIntelligenceEngine::class)->snapshot();

        return view('livewire.executive-control-tower', [
            'snap'     => $snap,
            'expanded' => $this->expanded,
        ]);
    }
}
