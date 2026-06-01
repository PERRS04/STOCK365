<?php

namespace App\Livewire;

use App\Models\Sede;
use App\WorkforceIntelligence\ImprovementEngine;
use App\WorkforceIntelligence\WorkforceAnalyticsEngine;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

class WorkforceDashboard extends Component
{
    public string $periodType = 'weekly';
    public ?int   $sedeFilter = null;

    public function mount(): void
    {
        $firstSede = Sede::where('activa', true)->orderBy('nombre')->first();
        $this->sedeFilter = $firstSede?->id;
    }

    #[Computed]
    public function sedes()
    {
        return Sede::where('activa', true)->orderBy('nombre')->get(['id', 'nombre']);
    }

    #[Computed]
    public function periodStart(): string
    {
        return match ($this->periodType) {
            'monthly' => now()->startOfMonth()->toDateString(),
            default   => now()->startOfWeek()->toDateString(),
        };
    }

    #[Computed]
    public function leaderboard()
    {
        if (!$this->sedeFilter) return collect();
        return app(WorkforceAnalyticsEngine::class)
            ->leaderboard($this->sedeFilter, $this->periodType, $this->periodStart);
    }

    #[Computed]
    public function teamHealth()
    {
        if (!$this->sedeFilter) return ['count' => 0, 'avg' => 0, 'distribution' => []];
        return app(WorkforceAnalyticsEngine::class)
            ->teamHealth($this->sedeFilter, $this->periodType, $this->periodStart);
    }

    #[Computed]
    public function teamImprovement()
    {
        if (!$this->sedeFilter) return collect();
        return app(ImprovementEngine::class)->teamImprovementRanking($this->sedeFilter);
    }

    #[Computed]
    public function coachingOpportunities()
    {
        if (!$this->sedeFilter) return collect();
        return app(WorkforceAnalyticsEngine::class)->coachingOpportunities($this->sedeFilter);
    }

    #[Computed]
    public function mostConsistent()
    {
        if (!$this->sedeFilter) return collect();
        return app(WorkforceAnalyticsEngine::class)->mostConsistent($this->sedeFilter);
    }

    public function render()
    {
        return view('livewire.workforce-dashboard');
    }
}
