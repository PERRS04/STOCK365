<?php

namespace App\OperationalIntelligence\Scoring;

use App\Models\ActivityLog;
use App\Models\CashClosing;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\CourtesyTransaction;
use App\Models\Sede;
use App\Models\StockAlert;
use App\Services\LiveCashBoxService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class HealthScoreEngine
{
    private const CACHE_TTL = 30;

    public function scoreAll(): Collection
    {
        return Sede::where('activa', true)
            ->where('is_demo', false)
            ->orderBy('nombre')
            ->get()
            ->map(fn ($sede) => array_merge(
                ['sede_id' => $sede->id, 'sede_name' => $sede->nombre],
                $this->scoreForSede($sede->id)
            ));
    }

    public function scoreForSede(int $sedeId): array
    {
        return Cache::remember("oi_health_{$sedeId}", self::CACHE_TTL, fn () =>
            $this->compute($sedeId)
        );
    }

    public function flush(int $sedeId): void
    {
        Cache::forget("oi_health_{$sedeId}");
    }

    // ─── Internal ───────────────────────────────────────────────────────────

    private function compute(int $sedeId): array
    {
        $cash        = $this->scoreCash($sedeId);
        $approvals   = $this->scoreApprovals($sedeId);
        $inventory   = $this->scoreInventory($sedeId);
        $operational = $this->scoreOperational($sedeId);

        $total = $cash['score'] + $approvals['score'] + $inventory['score'] + $operational['score'];

        return [
            'total'     => $total,
            'status'    => $this->statusFromScore($total),
            'breakdown' => compact('cash', 'approvals', 'inventory', 'operational'),
        ];
    }

    private function statusFromScore(int $score): string
    {
        return match(true) {
            $score >= 80 => 'healthy',
            $score >= 60 => 'warning',
            default      => 'critical',
        };
    }

    private function scoreCash(int $sedeId): array
    {
        $max    = 30;
        $earned = 0;
        $issues = [];

        $session = CashSession::where('sede_id', $sedeId)
            ->whereIn('status', ['open', 'pending_closing'])
            ->latest('opened_at')
            ->first();

        if (!$session) {
            return ['score' => 15, 'max' => $max, 'label' => 'Sin sesión activa', 'issues' => []];
        }

        $snap = app(LiveCashBoxService::class)->snapshot($session);

        if ($snap['total'] >= 0)  { $earned += 10; } else { $issues[] = 'Saldo negativo'; }
        if ($snap['total'] >= 50) { $earned += 5;  } else { $issues[] = 'Saldo bajo umbral'; }
        if ($snap['pending_mov'] === 0) { $earned += 10; } else { $issues[] = "{$snap['pending_mov']} mov. pendientes"; }
        if ($snap['pending_cor'] === 0) { $earned += 5;  } else { $issues[] = "{$snap['pending_cor']} cortesías pend."; }

        return [
            'score'  => $earned,
            'max'    => $max,
            'label'  => empty($issues) ? 'Saludable' : implode(' · ', array_slice($issues, 0, 2)),
            'issues' => $issues,
        ];
    }

    private function scoreApprovals(int $sedeId): array
    {
        $max    = 25;
        $earned = 0;
        $issues = [];

        $movCount     = CashMovement::where('sede_id', $sedeId)->where('status', 'pendiente')->count();
        $corCount     = CourtesyTransaction::where('sede_id', $sedeId)->where('status', 'pendiente')->count();
        $closingCount = CashClosing::where('sede_id', $sedeId)->where('estado', 'pendiente')->count();

        if ($movCount === 0)     { $earned += 10; } else { $issues[] = "{$movCount} movimientos"; }
        if ($corCount === 0)     { $earned += 8;  } else { $issues[] = "{$corCount} cortesías"; }
        if ($closingCount === 0) { $earned += 7;  } else { $issues[] = "{$closingCount} cierres"; }

        return [
            'score'  => $earned,
            'max'    => $max,
            'label'  => empty($issues) ? 'Al día' : 'Pendientes: ' . implode(', ', $issues),
            'issues' => $issues,
        ];
    }

    private function scoreInventory(int $sedeId): array
    {
        $max        = 25;
        $alertCount = StockAlert::where('sede_id', $sedeId)->where('alerta_activa', true)->count();
        $issues     = [];

        $earned = match(true) {
            $alertCount === 0  => 25,
            $alertCount <= 2   => 15,
            $alertCount <= 5   => 8,
            default            => 0,
        };

        if ($alertCount > 0) {
            $issues[] = "{$alertCount} alertas activas";
        }

        return [
            'score'  => $earned,
            'max'    => $max,
            'label'  => $alertCount === 0 ? 'Normal' : "{$alertCount} alerta(s) de stock",
            'issues' => $issues,
        ];
    }

    private function scoreOperational(int $sedeId): array
    {
        $max    = 20;
        $earned = 0;
        $issues = [];

        $sixteenHoursAgo = now()->subHours(16);
        $abandoned = CashSession::where('sede_id', $sedeId)
            ->where('status', 'open')
            ->where(fn ($q) => $q->whereDate('opened_at', '<', today())
                                  ->orWhere('opened_at', '<', $sixteenHoursAgo))
            ->count();

        if ($abandoned === 0) { $earned += 10; } else { $issues[] = 'Sesión abandonada'; }

        $hasActive = CashSession::where('sede_id', $sedeId)
            ->whereIn('status', ['open', 'pending_closing'])
            ->exists();

        if ($hasActive) $earned += 5;

        $escalations = ActivityLog::where('sede_id', $sedeId)
            ->where('action', 'operational_lock_escalation')
            ->where('created_at', '>=', now()->subHours(2))
            ->count();

        if ($escalations === 0) { $earned += 5; } else { $issues[] = 'Escalaciones recientes'; }

        return [
            'score'  => $earned,
            'max'    => $max,
            'label'  => empty($issues) ? 'Operativa' : implode(' · ', $issues),
            'issues' => $issues,
        ];
    }
}
