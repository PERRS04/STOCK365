<?php

namespace App\WorkforceIntelligence;

use App\Models\CashClosing;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\CourtesyTransaction;
use App\Models\WorkforceBehavioralPattern;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BehavioralPatternEngine
{
    private const DEPOSIT_TYPES = ['deposito', 'envio_boveda'];

    public function detectForUser(int $userId, int $sedeId, int $lookbackDays = 30): Collection
    {
        $since    = now()->subDays($lookbackDays)->startOfDay();
        $detected = collect();

        $this->detectExcessiveCourtesies($userId, $sedeId, $since, $detected);
        $this->detectRepeatedCashDifferences($userId, $sedeId, $since, $detected);
        $this->detectLateDeposits($userId, $sedeId, $since, $detected);
        $this->detectUnclosedSessions($userId, $sedeId, $since, $detected);

        // Deactivate patterns not found this run
        WorkforceBehavioralPattern::forUser($userId)
            ->active()
            ->whereNotIn('pattern_type', $detected->pluck('pattern_type')->toArray())
            ->update(['is_active' => false]);

        return $detected;
    }

    // ─── Detectors ───────────────────────────────────────────────────────────

    private function detectExcessiveCourtesies(int $userId, int $sedeId, Carbon $since, Collection &$detected): void
    {
        $courtesyCount = CourtesyTransaction::where('user_id', $userId)
            ->where('sede_id', $sedeId)
            ->where('created_at', '>=', $since)
            ->count();

        $sessionDays = CashSession::where('user_id', $userId)
            ->where('sede_id', $sedeId)
            ->where('opened_at', '>=', $since)
            ->selectRaw('COUNT(DISTINCT DATE(opened_at)) as days')
            ->value('days') ?? 1;

        $avgPerDay = $sessionDays > 0 ? $courtesyCount / $sessionDays : 0;

        if ($avgPerDay >= 3) {
            $this->upsertPattern($userId, $sedeId, 'excessive_courtesies', 'warning', [
                'avg_per_day'    => round($avgPerDay, 1),
                'total_period'   => $courtesyCount,
                'lookback_days'  => (int) $since->diffInDays(now()),
            ], $detected);
        }
    }

    private function detectRepeatedCashDifferences(int $userId, int $sedeId, Carbon $since, Collection &$detected): void
    {
        $closings = CashClosing::where('user_id', $userId)
            ->where('sede_id', $sedeId)
            ->where('fecha_cierre', '>=', $since)
            ->get(['diferencia', 'total_sistema']);

        if ($closings->count() < 3) return;

        $withDiffs = $closings->filter(fn ($c) => abs($c->diferencia) >= 1.0);
        $diffRate  = $withDiffs->count() / $closings->count();

        if ($diffRate >= 0.50) {
            $avgDiff = $withDiffs->avg(fn ($c) => abs($c->diferencia));
            $this->upsertPattern($userId, $sedeId, 'repeated_cash_differences', 'warning', [
                'diff_rate_pct' => round($diffRate * 100, 1),
                'avg_diff_amount' => round($avgDiff, 2),
                'closings_count' => $closings->count(),
            ], $detected);
        }
    }

    private function detectLateDeposits(int $userId, int $sedeId, Carbon $since, Collection &$detected): void
    {
        $deposits = CashMovement::where('user_id', $userId)
            ->where('sede_id', $sedeId)
            ->whereIn('type', self::DEPOSIT_TYPES)
            ->where('created_at', '>=', $since)
            ->with('cashSession:id,opened_at')
            ->get();

        if ($deposits->count() < 2) return;

        $lateDeposits = $deposits->filter(function ($mov) {
            if (!$mov->cashSession) return false;
            return $mov->cashSession->opened_at->diffInHours($mov->created_at) >= 4;
        });

        $lateRate = $lateDeposits->count() / $deposits->count();

        if ($lateRate >= 0.50) {
            $this->upsertPattern($userId, $sedeId, 'late_deposits', 'warning', [
                'late_rate_pct'  => round($lateRate * 100, 1),
                'late_count'     => $lateDeposits->count(),
                'total_deposits' => $deposits->count(),
            ], $detected);
        }
    }

    private function detectUnclosedSessions(int $userId, int $sedeId, Carbon $since, Collection &$detected): void
    {
        $unclosed = CashSession::where('user_id', $userId)
            ->where('sede_id', $sedeId)
            ->where('status', 'open')
            ->where('opened_at', '>=', $since)
            ->where('opened_at', '<', now()->subHours(20))
            ->count();

        if ($unclosed >= 2) {
            $this->upsertPattern($userId, $sedeId, 'unclosed_sessions', 'critical', [
                'unclosed_count' => $unclosed,
                'lookback_days'  => (int) $since->diffInDays(now()),
            ], $detected);
        }
    }

    // ─── Persistence ─────────────────────────────────────────────────────────

    private function upsertPattern(
        int    $userId,
        int    $sedeId,
        string $type,
        string $severity,
        array  $context,
        Collection &$detected
    ): void {
        $existing = WorkforceBehavioralPattern::where('user_id', $userId)
            ->where('pattern_type', $type)
            ->first();

        $pattern = WorkforceBehavioralPattern::updateOrCreate(
            ['user_id' => $userId, 'pattern_type' => $type],
            [
                'sede_id'          => $sedeId,
                'severity'         => $severity,
                'occurrence_count' => ($existing?->occurrence_count ?? 0) + 1,
                'first_seen'       => $existing?->first_seen ?? today(),
                'last_seen'        => today(),
                'context'          => $context,
                'is_active'        => true,
            ]
        );

        $detected->push($pattern);
    }
}
