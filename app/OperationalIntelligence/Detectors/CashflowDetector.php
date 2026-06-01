<?php

namespace App\OperationalIntelligence\Detectors;

use App\Enums\SignalSeverity;
use App\Enums\SignalType;
use App\Models\CashClosing;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Sale;
use App\Models\Sede;
use App\Models\User;
use App\OperationalIntelligence\Contracts\DetectorContract;
use App\Services\LiveCashBoxService;
use App\Values\OperationalSignal;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CashflowDetector implements DetectorContract
{
    private const STALL_MINUTES    = 45;
    private const DEPOSIT_WAIT     = 30;
    private const LONG_SESSION_HRS = 14;

    public function __construct(private readonly LiveCashBoxService $cashService) {}

    public function detect(): Collection
    {
        $signals = collect();

        $sessions = CashSession::whereIn('status', ['open', 'pending_closing'])
            ->with('user:id,name', 'sede:id,nombre')
            ->get();

        foreach ($sessions as $session) {
            $snapshot = $this->cashService->snapshot($session);

            $this->detectBalanceRisk($signals, $session, $snapshot);
            $this->detectCashStall($signals, $session);
            $this->detectLongSession($signals, $session);
            $this->detectDepositDelay($signals, $session, $snapshot);
        }

        $this->detectClosingAnomalies($signals);

        return $signals;
    }

    private function detectBalanceRisk(Collection $signals, CashSession $session, array $snap): void
    {
        if ($snap['total'] < 0) {
            $signals->push(new OperationalSignal(
                id:              "cash_negative_{$session->id}",
                type:            SignalType::CASH_RISK,
                severity:        SignalSeverity::CRITICAL,
                title:           "Caja negativa · {$session->sede?->nombre}",
                body:            "Balance en -$" . number_format(abs($snap['total']), 2) . ". Requiere revisión inmediata.",
                sedeId:          $session->sede_id,
                sedeName:        $session->sede?->nombre,
                operatorId:      $session->user_id,
                operatorName:    $session->user?->name,
                detectedAt:      now(),
                confidence:      1.0,
                suggestedAction: 'Revisar movimientos y aprobar depósito pendiente',
                context:         ['total' => $snap['total'], 'session_id' => $session->id],
                isPredictive:    false,
            ));
        } elseif ($snap['total'] < 50) {
            $signals->push(new OperationalSignal(
                id:              "cash_low_{$session->id}",
                type:            SignalType::CASH_RISK,
                severity:        SignalSeverity::WARNING,
                title:           "Saldo crítico · {$session->sede?->nombre}",
                body:            "Balance de $" . number_format($snap['total'], 2) . " está por debajo del umbral mínimo.",
                sedeId:          $session->sede_id,
                sedeName:        $session->sede?->nombre,
                operatorId:      $session->user_id,
                operatorName:    $session->user?->name,
                detectedAt:      now(),
                confidence:      0.95,
                suggestedAction: 'Verificar cambio pendiente o depósito por aprobar',
                context:         ['total' => $snap['total'], 'session_id' => $session->id],
                isPredictive:    false,
            ));
        }
    }

    private function detectCashStall(Collection $signals, CashSession $session): void
    {
        $openMinutes = $session->opened_at->diffInMinutes(now());
        if ($openMinutes < self::STALL_MINUTES) return;

        $lastSaleTs = Sale::where('cash_session_id', $session->id)
            ->latest('fecha_venta')
            ->value('fecha_venta');

        $minutesSinceSale = $lastSaleTs
            ? Carbon::parse($lastSaleTs)->diffInMinutes(now())
            : $openMinutes;

        if ($minutesSinceSale < self::STALL_MINUTES) return;

        $signals->push(new OperationalSignal(
            id:              "cash_stall_{$session->id}",
            type:            SignalType::CASH_STALL,
            severity:        $minutesSinceSale > 90 ? SignalSeverity::WARNING : SignalSeverity::NOTICE,
            title:           "Caja sin actividad · {$session->sede?->nombre}",
            body:            "Sin ventas en " . (int) $minutesSinceSale . " min. Sesión activa hace {$session->duration}h.",
            sedeId:          $session->sede_id,
            sedeName:        $session->sede?->nombre,
            operatorId:      $session->user_id,
            operatorName:    $session->user?->name,
            detectedAt:      now(),
            confidence:      0.80,
            suggestedAction: 'Verificar presencia del operador',
            context:         ['minutes_since_sale' => (int) $minutesSinceSale, 'open_minutes' => (int) $openMinutes],
            isPredictive:    false,
        ));
    }

    private function detectLongSession(Collection $signals, CashSession $session): void
    {
        $hours = $session->opened_at->diffInHours(now());
        if ($hours < self::LONG_SESSION_HRS) return;

        $signals->push(new OperationalSignal(
            id:              "session_long_{$session->id}",
            type:            SignalType::OPERATIONAL_STALL,
            severity:        $hours >= 16 ? SignalSeverity::CRITICAL : SignalSeverity::WARNING,
            title:           "Sesión extendida · {$session->sede?->nombre}",
            body:            "Caja abierta hace {$hours}h por {$session->user?->name}. No se ha generado cierre.",
            sedeId:          $session->sede_id,
            sedeName:        $session->sede?->nombre,
            operatorId:      $session->user_id,
            operatorName:    $session->user?->name,
            detectedAt:      now(),
            confidence:      1.0,
            suggestedAction: 'Iniciar cierre de caja o forzar cierre de sesión',
            context:         ['hours_open' => $hours, 'session_id' => $session->id],
            isPredictive:    false,
        ));
    }

    private function detectDepositDelay(Collection $signals, CashSession $session, array $snap): void
    {
        if ($snap['pending_mov'] === 0) return;

        $oldestTs = CashMovement::where('cash_session_id', $session->id)
            ->where('status', 'pendiente')
            ->orderBy('created_at')
            ->value('created_at');

        if (!$oldestTs) return;

        $waitMinutes = Carbon::parse($oldestTs)->diffInMinutes(now());
        if ($waitMinutes < self::DEPOSIT_WAIT) return;

        $signals->push(new OperationalSignal(
            id:              "deposit_delay_{$session->id}",
            type:            SignalType::DEPOSIT_DELAY,
            severity:        $waitMinutes > 60 ? SignalSeverity::WARNING : SignalSeverity::NOTICE,
            title:           "Movimiento sin aprobar · {$session->sede?->nombre}",
            body:            "{$snap['pending_mov']} movimiento(s) en espera. El más antiguo lleva {$waitMinutes} min.",
            sedeId:          $session->sede_id,
            sedeName:        $session->sede?->nombre,
            operatorId:      $session->user_id,
            operatorName:    $session->user?->name,
            detectedAt:      now(),
            confidence:      0.90,
            suggestedAction: 'Revisar aprobaciones pendientes en el centro de control',
            context:         ['pending_count' => $snap['pending_mov'], 'wait_minutes' => $waitMinutes],
            isPredictive:    false,
        ));
    }

    private function detectClosingAnomalies(Collection $signals): void
    {
        $rows = CashClosing::where('created_at', '>=', now()->subDays(7))
            ->whereRaw('ABS(diferencia) > 5')
            ->selectRaw('user_id, COUNT(*) as cnt, SUM(ABS(diferencia)) as total_diff, MAX(created_at) as last_at')
            ->groupBy('user_id')
            ->havingRaw('cnt >= 2')
            ->get();

        if ($rows->isEmpty()) return;

        $userIds = $rows->pluck('user_id');
        $users   = User::whereIn('id', $userIds)->pluck('name', 'id');

        $latestSessions = CashSession::whereIn('user_id', $userIds)
            ->orderByDesc('opened_at')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        $sedeIds = $latestSessions->pluck('sede_id')->filter();
        $sedes   = Sede::whereIn('id', $sedeIds)->pluck('nombre', 'id');

        foreach ($rows as $row) {
            $userName = $users[$row->user_id] ?? "Operador #{$row->user_id}";
            $session  = $latestSessions[$row->user_id] ?? null;
            $sedeId   = $session?->sede_id;
            $sedeName = $sedeId ? ($sedes[$sedeId] ?? null) : null;

            $signals->push(new OperationalSignal(
                id:              "closing_anomaly_{$row->user_id}",
                type:            SignalType::CLOSING_ANOMALY,
                severity:        $row->cnt >= 4 ? SignalSeverity::WARNING : SignalSeverity::NOTICE,
                title:           "Patrón de diferencias · {$userName}",
                body:            "{$row->cnt} cierres con diferencia en 7 días. Acumulado: $" . number_format($row->total_diff, 2) . ".",
                sedeId:          $sedeId,
                sedeName:        $sedeName,
                operatorId:      $row->user_id,
                operatorName:    $userName,
                detectedAt:      Carbon::parse($row->last_at),
                confidence:      min(0.95, 0.60 + ($row->cnt * 0.08)),
                suggestedAction: 'Revisar procedimiento de cierre con el operador',
                context:         ['closing_count' => $row->cnt, 'total_difference' => $row->total_diff],
                isPredictive:    false,
            ));
        }
    }
}
