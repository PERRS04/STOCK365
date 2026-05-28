<?php

namespace App\Services\LiveCashEngine;

use App\Enums\CashEventType;
use App\Enums\CashRiskLevel;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\CourtesyTransaction;
use App\Models\Sale;
use App\Services\LiveCashBoxService;
use App\Values\CashRealtimeEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * LiveCashEngineService
 *
 * Wraps LiveCashBoxService and adds:
 * ─ Per-session cache (10s for snapshot, 8s for event stream).
 * ─ Typed CashRealtimeEvent stream including pending items.
 * ─ Zero-query CashRiskLevel derived from cached snapshot.
 *
 * BossLiveOverview continues to call LiveCashBoxService directly — this
 * service is purpose-built for the operator's Live Cash Engine view.
 */
class LiveCashEngineService
{
    private const SNAPSHOT_TTL = 10;
    private const STREAM_TTL   = 8;

    private const DEBIT_TYPES = [
        'retiro', 'pago_proveedor', 'envio_boveda', 'gasto_operativo', 'deposito',
    ];

    // ─── Public API ─────────────────────────────────────────────────────────

    public function snapshot(CashSession $session): array
    {
        return Cache::remember(
            "ce_snap_{$session->id}",
            self::SNAPSHOT_TTL,
            fn () => app(LiveCashBoxService::class)->snapshot($session)
        );
    }

    public function eventStream(CashSession $session, int $limit = 25): Collection
    {
        return Cache::remember(
            "ce_stream_{$session->id}",
            self::STREAM_TTL,
            fn () => $this->buildStream($session, $limit)
        );
    }

    public function risk(CashSession $session, array $snap): CashRiskLevel
    {
        if ($snap['total'] < 0) {
            return CashRiskLevel::NEGATIVE;
        }

        // Stale pending: any movement waiting and session open > 20min
        if ($snap['pending_mov'] > 0
            && $session->opened_at->diffInMinutes(now()) > 20) {
            return CashRiskLevel::STALE_PENDING;
        }

        if ($snap['total'] < 50) {
            return CashRiskLevel::LOW_BALANCE;
        }

        return CashRiskLevel::NORMAL;
    }

    public function flush(int $sessionId): void
    {
        Cache::forget("ce_snap_{$sessionId}");
        Cache::forget("ce_stream_{$sessionId}");
    }

    // ─── Stream builder ──────────────────────────────────────────────────────

    private function buildStream(CashSession $session, int $limit): Collection
    {
        $sessionId = $session->id;
        $userId    = $session->user_id;
        $sedeId    = $session->sede_id;
        $openedAt  = $session->opened_at;

        // ── Completed sales ──────────────────────────────────────────────────
        $sales = Sale::where('cash_session_id', $sessionId)
            ->where('estado', 'completada')
            ->withCount('items')
            ->latest('fecha_venta')
            ->limit(20)
            ->get()
            ->map(fn ($s) => new CashRealtimeEvent(
                id:        "sale_{$s->id}",
                type:      CashEventType::SALE,
                amount:    (float) $s->total_sistema,
                direction: '+',
                label:     'Venta',
                detail:    $s->items_count . ($s->items_count === 1 ? ' producto' : ' productos'),
                timestamp: $s->fecha_venta instanceof Carbon
                    ? $s->fecha_venta
                    : Carbon::parse($s->fecha_venta),
                status:    'completed',
                isPending: false,
            ));

        // ── Approved movements ───────────────────────────────────────────────
        $approved = CashMovement::where('cash_session_id', $sessionId)
            ->where('status', 'aprobado')
            ->latest('approved_at')
            ->limit(10)
            ->get()
            ->map(fn ($m) => new CashRealtimeEvent(
                id:        "mov_{$m->id}",
                type:      in_array($m->type, self::DEBIT_TYPES)
                    ? CashEventType::WITHDRAWAL
                    : CashEventType::INFLOW,
                amount:    (float) $m->amount,
                direction: in_array($m->type, self::DEBIT_TYPES) ? '-' : '+',
                label:     CashMovement::typeLabel($m->type),
                detail:    $m->motivo,
                timestamp: $m->approved_at ?? $m->updated_at,
                status:    'completed',
                isPending: false,
            ));

        // ── Pending movements ────────────────────────────────────────────────
        $pendingMov = CashMovement::where('cash_session_id', $sessionId)
            ->where('status', 'pendiente')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($m) => new CashRealtimeEvent(
                id:        "pending_mov_{$m->id}",
                type:      CashEventType::PENDING_MOVEMENT,
                amount:    (float) $m->amount,
                direction: in_array($m->type, self::DEBIT_TYPES) ? '-' : '+',
                label:     CashMovement::typeLabel($m->type),
                detail:    $m->motivo,
                timestamp: $m->created_at,
                status:    'pending',
                isPending: true,
            ));

        // ── Approved courtesies ──────────────────────────────────────────────
        $courtesies = CourtesyTransaction::where('sede_id', $sedeId)
            ->where('user_id', $userId)
            ->where('status', 'aprobado')
            ->where('created_at', '>=', $openedAt)
            ->with('product:id,nombre,precio_venta')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($c) => new CashRealtimeEvent(
                id:        "courtesy_{$c->id}",
                type:      CashEventType::COURTESY,
                amount:    $c->quantity * (float) ($c->product?->precio_venta ?? 0),
                direction: '-',
                label:     'Cortesía',
                detail:    $c->product?->nombre ?? '',
                timestamp: $c->approved_at ?? $c->created_at,
                status:    'completed',
                isPending: false,
            ));

        // ── Pending courtesies ───────────────────────────────────────────────
        $pendingCor = CourtesyTransaction::where('sede_id', $sedeId)
            ->where('user_id', $userId)
            ->where('status', 'pendiente')
            ->where('created_at', '>=', $openedAt)
            ->with('product:id,nombre')
            ->latest()
            ->limit(3)
            ->get()
            ->map(fn ($c) => new CashRealtimeEvent(
                id:        "pending_courtesy_{$c->id}",
                type:      CashEventType::PENDING_COURTESY,
                amount:    0.0,
                direction: '',
                label:     'Cortesía pendiente',
                detail:    $c->product?->nombre ?? '',
                timestamp: $c->created_at,
                status:    'pending',
                isPending: true,
            ));

        // ── Session open marker ──────────────────────────────────────────────
        $sessionOpen = collect([new CashRealtimeEvent(
            id:        "session_open_{$session->id}",
            type:      CashEventType::SESSION_OPEN,
            amount:    (float) $session->opening_amount,
            direction: '',
            label:     'Apertura de caja',
            detail:    'Inicio de turno · ' . $session->duration . 'h',
            timestamp: $openedAt,
            status:    'completed',
            isPending: false,
        )]);

        // Pending first (newest first), then approved desc by time, then opener last
        $pendingAll  = $pendingMov->merge($pendingCor)->sortByDesc('timestamp')->values();
        $approvedAll = $sales->merge($approved)->merge($courtesies)
            ->sortByDesc('timestamp')
            ->take($limit)
            ->values();

        return $pendingAll->merge($approvedAll)->merge($sessionOpen)->values();
    }
}
