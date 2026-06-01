<?php

namespace App\OperationalIntelligence\Memory;

use App\Models\CashSession;
use App\Models\OperationalSnapshot;
use App\Models\Sede;
use App\OperationalIntelligence\OperationalSignalEngine;
use App\OperationalIntelligence\Scoring\HealthScoreEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OperationalSnapshotEngine
{
    public function __construct(
        private readonly HealthScoreEngine     $health,
        private readonly OperationalSignalEngine $signals,
    ) {}

    public function captureAll(): int
    {
        $sedes   = Sede::where('activa', true)->pluck('id');
        $captured = 0;

        foreach ($sedes as $sedeId) {
            try {
                $this->captureForSede($sedeId);
                $captured++;
            } catch (\Throwable $e) {
                Log::warning("SnapshotEngine: failed for sede {$sedeId} — {$e->getMessage()}");
            }
        }

        return $captured;
    }

    public function captureForSede(int $sedeId): OperationalSnapshot
    {
        $hourKey   = now()->startOfHour();
        $scoreData = $this->health->scoreForSede($sedeId);
        $breakdown = $scoreData['breakdown'] ?? [];

        $sedeSignals  = $this->signals->detectForSede($sedeId);
        $criticalCount = $sedeSignals->filter(fn ($s) => $s->isCritical())->count();
        $warningCount  = $sedeSignals->filter(fn ($s) => !$s->isCritical())->count();

        $activeSession = CashSession::where('sede_id', $sedeId)->active()->first();
        $cashTotal     = null;
        if ($activeSession) {
            $cashTotal = DB::table('cash_movements')
                ->where('cash_session_id', $activeSession->id)
                ->where('status', 'aprobado')
                ->selectRaw("SUM(CASE WHEN type IN ('deposito','cambio') THEN amount ELSE -amount END) as total")
                ->value('total');
            $cashTotal = ($cashTotal ?? 0) + ($activeSession->opening_amount ?? 0);
        }

        return OperationalSnapshot::updateOrCreate(
            [
                'sede_id'     => $sedeId,
                'captured_at' => $hourKey,
            ],
            [
                'health_score'       => $scoreData['total'] ?? 0,
                'status'             => $scoreData['status'] ?? 'healthy',
                'cash_score'         => $breakdown['cash']['score']        ?? 0,
                'approval_score'     => $breakdown['approvals']['score']   ?? 0,
                'inventory_score'    => $breakdown['inventory']['score']   ?? 0,
                'operational_score'  => $breakdown['operational']['score'] ?? 0,
                'signal_count'       => $sedeSignals->count(),
                'critical_signals'   => $criticalCount,
                'warning_signals'    => $warningCount,
                'has_active_session' => $activeSession !== null,
                'cash_total'         => $cashTotal,
            ]
        );
    }
}
