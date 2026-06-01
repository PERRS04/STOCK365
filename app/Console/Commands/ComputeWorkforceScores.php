<?php

namespace App\Console\Commands;

use App\Models\User;
use App\WorkforceIntelligence\BehavioralPatternEngine;
use App\WorkforceIntelligence\WorkforceAnalyticsEngine;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ComputeWorkforceScores extends Command
{
    protected $signature   = 'workforce:compute
                                {--period=weekly : Period type: daily, weekly, monthly}
                                {--sede=         : Restrict to a specific sede_id}
                                {--user=         : Restrict to a specific user_id}';
    protected $description = 'Compute workforce performance scores and detect behavioral patterns';

    public function handle(
        WorkforceAnalyticsEngine $analytics,
        BehavioralPatternEngine  $patterns
    ): int {
        $periodType = $this->option('period');
        [$start, $end] = $this->periodDates($periodType);

        $query = User::where('role', 'operador')->whereNotNull('sede_id');

        if ($sedeId = $this->option('sede')) {
            $query->where('sede_id', (int) $sedeId);
        }
        if ($userId = $this->option('user')) {
            $query->where('id', (int) $userId);
        }

        $users     = $query->get(['id', 'sede_id', 'name']);
        $computed  = 0;
        $failed    = 0;

        $this->withProgressBar($users, function ($user) use ($analytics, $patterns, $periodType, $start, $end, &$computed, &$failed) {
            try {
                $analytics->computeForUser($user->id, $user->sede_id, $periodType, $start, $end);
                $patterns->detectForUser($user->id, $user->sede_id);
                $computed++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning("workforce:compute failed for user {$user->id}: {$e->getMessage()}");
            }
        });

        $this->newLine();
        $this->info("Computed {$computed} scores. Failed: {$failed}.");

        return self::SUCCESS;
    }

    private function periodDates(string $type): array
    {
        return match ($type) {
            'daily'   => [now()->startOfDay(), now()->endOfDay()],
            'monthly' => [now()->startOfMonth(), now()->endOfMonth()],
            default   => [now()->startOfWeek(), now()->endOfWeek()],
        };
    }
}
