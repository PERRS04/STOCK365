<?php

namespace App\Livewire;

use App\OperationalIntelligence\Context\OperationalContextBuilder;
use App\OperationalIntelligence\Summaries\OperationalSummaryEngine;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class OperationalCopilot extends Component
{
    public bool $showContext = false;

    public function toggleContext(): void
    {
        $this->showContext = !$this->showContext;
    }

    public function render()
    {
        abort_unless(Auth::user()?->isBoss(), 403);

        $summaryEngine  = app(OperationalSummaryEngine::class);
        $contextBuilder = app(OperationalContextBuilder::class);

        $summaries  = $summaryEngine->summarize();
        $digest     = $summaryEngine->systemDigest();
        $context    = $contextBuilder->build();

        // Show: up to 3 actionable summaries + 1 positive if space allows
        $actionable = $summaries->filter(fn ($s) => $s->isActionable())->take(3);
        $positive   = $summaries->filter(fn ($s) => $s->isPositive())->take(1);

        $displaySummaries = $actionable->isEmpty()
            ? $positive->take(2)
            : $actionable;

        $promptContext = $this->showContext
            ? $contextBuilder->buildForClaude()
            : null;

        return view('livewire.operational-copilot', compact(
            'summaries', 'displaySummaries', 'digest', 'context', 'promptContext'
        ));
    }
}
