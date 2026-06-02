<?php

namespace App\OperationalIntelligence\Detectors;

use App\Enums\SignalSeverity;
use App\Enums\SignalType;
use App\Models\ActivityLog;
use App\Models\CashSession;
use App\Models\CourtesyTransaction;
use App\Models\Sede;
use App\Models\User;
use App\OperationalIntelligence\Contracts\DetectorContract;
use App\Values\OperationalSignal;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class OperatorBehaviorDetector implements DetectorContract
{
    private const COURTESY_THRESHOLD = 3;
    private const IDLE_HOURS         = 2;

    public function detect(): Collection
    {
        $signals = collect();

        $this->detectExcessCourtesies($signals);
        $this->detectIdleOperators($signals);
        $this->detectEscalationPattern($signals);
        $this->detectOffHoursActivity($signals);

        return $signals;
    }

    private function detectExcessCourtesies(Collection $signals): void
    {
        $demoIds = Sede::demoIds();

        $rows = CourtesyTransaction::where('created_at', '>=', now()->startOfDay())
            ->whereNotIn('sede_id', $demoIds)
            ->whereIn('status', ['pendiente', 'aprobado'])
            ->selectRaw('user_id, sede_id, COUNT(*) as cnt')
            ->groupBy('user_id', 'sede_id')
            ->havingRaw('cnt > ?', [self::COURTESY_THRESHOLD])
            ->get();

        if ($rows->isEmpty()) return;

        $userIds = $rows->pluck('user_id')->unique();
        $sedeIds = $rows->pluck('sede_id')->unique();
        $users   = User::whereIn('id', $userIds)->pluck('name', 'id');
        $sedes   = Sede::whereIn('id', $sedeIds)->pluck('nombre', 'id');

        foreach ($rows as $row) {
            $userName = $users[$row->user_id] ?? "Operador #{$row->user_id}";
            $sedeName = $sedes[$row->sede_id] ?? "Sede #{$row->sede_id}";

            $signals->push(new OperationalSignal(
                id:              "excess_courtesy_{$row->user_id}_{$row->sede_id}",
                type:            SignalType::OPERATOR_RISK,
                severity:        $row->cnt > 6 ? SignalSeverity::WARNING : SignalSeverity::NOTICE,
                title:           "Alto volumen de cortesías · {$userName}",
                body:            "{$row->cnt} cortesías registradas hoy en {$sedeName}. Umbral: " . self::COURTESY_THRESHOLD . ".",
                sedeId:          $row->sede_id,
                sedeName:        $sedeName,
                operatorId:      $row->user_id,
                operatorName:    $userName,
                detectedAt:      now(),
                confidence:      0.85,
                suggestedAction: 'Revisar motivos de cortesías y verificar procedimiento',
                context:         ['courtesy_count' => $row->cnt, 'threshold' => self::COURTESY_THRESHOLD],
                isPredictive:    false,
            ));
        }
    }

    private function detectIdleOperators(Collection $signals): void
    {
        $idleThreshold = now()->subHours(self::IDLE_HOURS);
        $demoIds       = Sede::demoIds();

        $sessions = CashSession::where('status', 'open')
            ->whereDate('opened_at', today())
            ->whereNotIn('sede_id', $demoIds)
            ->with('user:id,name', 'sede:id,nombre')
            ->get();

        foreach ($sessions as $session) {
            $lastActivity = ActivityLog::where('user_id', $session->user_id)
                ->latest()
                ->value('created_at');

            if (!$lastActivity) continue;

            $lastActivityTs = Carbon::parse($lastActivity);
            if ($lastActivityTs->greaterThan($idleThreshold)) continue;

            $idleMinutes = $lastActivityTs->diffInMinutes(now());

            $signals->push(new OperationalSignal(
                id:              "operator_idle_{$session->user_id}",
                type:            SignalType::OPERATOR_RISK,
                severity:        $idleMinutes > 180 ? SignalSeverity::WARNING : SignalSeverity::NOTICE,
                title:           "Operador sin actividad · {$session->user?->name}",
                body:            "Sin actividad registrada en {$idleMinutes} min. Sesión activa en {$session->sede?->nombre}.",
                sedeId:          $session->sede_id,
                sedeName:        $session->sede?->nombre,
                operatorId:      $session->user_id,
                operatorName:    $session->user?->name,
                detectedAt:      now(),
                confidence:      0.75,
                suggestedAction: 'Verificar estado del operador y la caja',
                context:         ['idle_minutes' => $idleMinutes, 'session_id' => $session->id],
                isPredictive:    false,
            ));
        }
    }

    private function detectEscalationPattern(Collection $signals): void
    {
        $demoIds = Sede::demoIds();

        $rows = ActivityLog::where('action', 'operational_lock_escalation')
            ->whereNotIn('sede_id', $demoIds)
            ->where('created_at', '>=', now()->subHours(4))
            ->selectRaw('user_id, user_name, sede_id, COUNT(*) as cnt, MAX(created_at) as last_at')
            ->groupBy('user_id', 'user_name', 'sede_id')
            ->havingRaw('cnt >= 2')
            ->get();

        if ($rows->isEmpty()) return;

        $sedeIds = $rows->pluck('sede_id')->unique()->filter();
        $sedes   = Sede::whereIn('id', $sedeIds)->pluck('nombre', 'id');

        foreach ($rows as $row) {
            $sedeName = $row->sede_id ? ($sedes[$row->sede_id] ?? "Sede #{$row->sede_id}") : null;

            $signals->push(new OperationalSignal(
                id:              "escalation_{$row->user_id}",
                type:            SignalType::ANOMALOUS_ACTIVITY,
                severity:        SignalSeverity::WARNING,
                title:           "Escalaciones repetidas · {$row->user_name}",
                body:            "{$row->cnt} escalaciones de bloqueo en las últimas 4 horas. Puede indicar un problema operacional recurrente.",
                sedeId:          $row->sede_id,
                sedeName:        $sedeName,
                operatorId:      $row->user_id,
                operatorName:    $row->user_name,
                detectedAt:      Carbon::parse($row->last_at),
                confidence:      0.90,
                suggestedAction: 'Contactar al operador y revisar estado de la sesión',
                context:         ['escalation_count' => $row->cnt],
                isPredictive:    false,
            ));
        }
    }

    private function detectOffHoursActivity(Collection $signals): void
    {
        $demoIds = Sede::demoIds();

        $rows = ActivityLog::where('created_at', '>=', now()->subDay())
            ->whereNotIn('sede_id', $demoIds)
            ->where(function ($q) {
                $q->whereTime('created_at', '>=', '23:00:00')
                  ->orWhereTime('created_at', '<=', '05:30:00');
            })
            ->selectRaw('user_id, user_name, sede_id, COUNT(*) as cnt, MAX(created_at) as last_at')
            ->groupBy('user_id', 'user_name', 'sede_id')
            ->havingRaw('cnt >= 3')
            ->get();

        if ($rows->isEmpty()) return;

        $sedeIds = $rows->pluck('sede_id')->unique()->filter();
        $sedes   = Sede::whereIn('id', $sedeIds)->pluck('nombre', 'id');

        foreach ($rows as $row) {
            $sedeName = $row->sede_id ? ($sedes[$row->sede_id] ?? null) : null;
            $lastAt   = Carbon::parse($row->last_at);

            $signals->push(new OperationalSignal(
                id:              "off_hours_{$row->user_id}",
                type:            SignalType::ANOMALOUS_ACTIVITY,
                severity:        SignalSeverity::NOTICE,
                title:           "Actividad fuera de horario · {$row->user_name}",
                body:            "{$row->cnt} acciones registradas entre las 23:00 y 05:30 h. Último: {$lastAt->format('H:i')}.",
                sedeId:          $row->sede_id,
                sedeName:        $sedeName,
                operatorId:      $row->user_id,
                operatorName:    $row->user_name,
                detectedAt:      $lastAt,
                confidence:      0.65,
                suggestedAction: 'Revisar log de actividad del operador',
                context:         ['action_count' => $row->cnt, 'last_action' => $lastAt->toISOString()],
                isPredictive:    false,
            ));
        }
    }
}
