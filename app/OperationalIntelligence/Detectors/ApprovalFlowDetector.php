<?php

namespace App\OperationalIntelligence\Detectors;

use App\Enums\SignalSeverity;
use App\Enums\SignalType;
use App\Models\CashClosing;
use App\Models\CashMovement;
use App\Models\CourtesyTransaction;
use App\Models\Sede;
use App\OperationalIntelligence\Contracts\DetectorContract;
use App\Values\OperationalSignal;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ApprovalFlowDetector implements DetectorContract
{
    private const MOV_WAIT_MIN      = 30;
    private const COURTESY_WAIT_MIN = 45;
    private const CLOSING_WAIT_MIN  = 120;

    public function detect(): Collection
    {
        $signals = collect();

        $this->detectStaleCashMovements($signals);
        $this->detectStaleCourtesies($signals);
        $this->detectStaleClosings($signals);

        return $signals;
    }

    private function detectStaleCashMovements(Collection $signals): void
    {
        $demoIds = Sede::demoIds();

        $rows = CashMovement::where('status', 'pendiente')
            ->whereNotIn('sede_id', $demoIds)
            ->where('created_at', '<=', now()->subMinutes(self::MOV_WAIT_MIN))
            ->selectRaw('sede_id, COUNT(*) as cnt, MIN(created_at) as oldest')
            ->groupBy('sede_id')
            ->get();

        if ($rows->isEmpty()) return;

        $sedes = Sede::whereIn('id', $rows->pluck('sede_id'))->pluck('nombre', 'id');

        foreach ($rows as $row) {
            $waitMin  = Carbon::parse($row->oldest)->diffInMinutes(now());
            $sedeName = $sedes[$row->sede_id] ?? "Sede #{$row->sede_id}";

            $signals->push(new OperationalSignal(
                id:              "approval_mov_{$row->sede_id}",
                type:            SignalType::APPROVAL_BOTTLENECK,
                severity:        $waitMin > 60 ? SignalSeverity::WARNING : SignalSeverity::NOTICE,
                title:           "Movimientos represados · {$sedeName}",
                body:            "{$row->cnt} movimiento(s) de caja esperando aprobación. El más antiguo lleva {$waitMin} min.",
                sedeId:          $row->sede_id,
                sedeName:        $sedeName,
                operatorId:      null,
                operatorName:    null,
                detectedAt:      now(),
                confidence:      0.95,
                suggestedAction: 'Revisar y aprobar movimientos en el panel de aprobaciones',
                context:         ['count' => $row->cnt, 'wait_minutes' => $waitMin],
                isPredictive:    false,
            ));
        }
    }

    private function detectStaleCourtesies(Collection $signals): void
    {
        $demoIds = Sede::demoIds();

        $rows = CourtesyTransaction::where('status', 'pendiente')
            ->whereNotIn('sede_id', $demoIds)
            ->where('created_at', '<=', now()->subMinutes(self::COURTESY_WAIT_MIN))
            ->selectRaw('sede_id, COUNT(*) as cnt, MIN(created_at) as oldest')
            ->groupBy('sede_id')
            ->get();

        if ($rows->isEmpty()) return;

        $sedes = Sede::whereIn('id', $rows->pluck('sede_id'))->pluck('nombre', 'id');

        foreach ($rows as $row) {
            $waitMin  = Carbon::parse($row->oldest)->diffInMinutes(now());
            $sedeName = $sedes[$row->sede_id] ?? "Sede #{$row->sede_id}";

            $signals->push(new OperationalSignal(
                id:              "approval_courtesy_{$row->sede_id}",
                type:            SignalType::APPROVAL_BOTTLENECK,
                severity:        SignalSeverity::NOTICE,
                title:           "Cortesías sin aprobar · {$sedeName}",
                body:            "{$row->cnt} cortesía(s) pendiente(s) de aprobación hace {$waitMin} min.",
                sedeId:          $row->sede_id,
                sedeName:        $sedeName,
                operatorId:      null,
                operatorName:    null,
                detectedAt:      now(),
                confidence:      0.85,
                suggestedAction: 'Revisar cortesías en cola de aprobaciones',
                context:         ['count' => $row->cnt, 'wait_minutes' => $waitMin],
                isPredictive:    false,
            ));
        }
    }

    private function detectStaleClosings(Collection $signals): void
    {
        $demoIds = Sede::demoIds();

        $rows = CashClosing::where('estado', 'pendiente')
            ->whereNotIn('sede_id', $demoIds)
            ->where('created_at', '<=', now()->subMinutes(self::CLOSING_WAIT_MIN))
            ->selectRaw('sede_id, COUNT(*) as cnt, MIN(created_at) as oldest')
            ->groupBy('sede_id')
            ->get();

        if ($rows->isEmpty()) return;

        $sedes = Sede::whereIn('id', $rows->pluck('sede_id'))->pluck('nombre', 'id');

        foreach ($rows as $row) {
            $waitMin  = Carbon::parse($row->oldest)->diffInMinutes(now());
            $sedeName = $sedes[$row->sede_id] ?? "Sede #{$row->sede_id}";

            $signals->push(new OperationalSignal(
                id:              "stale_closing_{$row->sede_id}",
                type:            SignalType::CLOSING_ANOMALY,
                severity:        $waitMin > 360 ? SignalSeverity::WARNING : SignalSeverity::NOTICE,
                title:           "Cierre pendiente · {$sedeName}",
                body:            "{$row->cnt} cierre(s) de caja sin revisión desde hace {$waitMin} min.",
                sedeId:          $row->sede_id,
                sedeName:        $sedeName,
                operatorId:      null,
                operatorName:    null,
                detectedAt:      now(),
                confidence:      0.90,
                suggestedAction: 'Aprobar o rechazar el cierre pendiente',
                context:         ['count' => $row->cnt, 'wait_minutes' => $waitMin],
                isPredictive:    false,
            ));
        }
    }
}
