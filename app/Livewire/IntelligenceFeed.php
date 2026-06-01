<?php

namespace App\Livewire;

use App\Enums\SignalSeverity;
use App\OperationalIntelligence\OperationalSignalEngine;
use App\OperationalIntelligence\Scoring\HealthScoreEngine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class IntelligenceFeed extends Component
{
    public function render()
    {
        abort_unless(Auth::user()?->isBoss(), 403);

        $engine   = app(OperationalSignalEngine::class);
        $scorer   = app(HealthScoreEngine::class);

        $signals      = $engine->detect();
        $healthScores = $scorer->scoreAll();

        $criticalCount = $signals->filter(fn ($s) => $s->severity === SignalSeverity::CRITICAL)->count();
        $warningCount  = $signals->filter(fn ($s) => $s->severity === SignalSeverity::WARNING)->count();
        $noticeCount   = $signals->filter(fn ($s) => $s->severity === SignalSeverity::NOTICE)->count();
        $totalCount    = $signals->count();

        // Group signals by severity (desc) for the feed
        $grouped = $signals->groupBy(fn ($s) => $s->severity->value)->sortKeysDesc();

        $overallHealth = $this->computeOverallHealth($healthScores);

        return view('livewire.intelligence-feed', compact(
            'signals', 'healthScores', 'grouped',
            'criticalCount', 'warningCount', 'noticeCount', 'totalCount',
            'overallHealth'
        ));
    }

    private function computeOverallHealth(Collection $scores): string
    {
        if ($scores->isEmpty()) return 'healthy';

        $avg = $scores->avg('total');

        return match(true) {
            $avg >= 80 => 'healthy',
            $avg >= 60 => 'warning',
            default    => 'critical',
        };
    }
}
