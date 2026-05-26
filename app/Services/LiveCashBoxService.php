<?php

namespace App\Services;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\CourtesyTransaction;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class LiveCashBoxService
{
    /**
     * Compute a real-time snapshot of the physical cash in the box
     * for a given active cash session.
     */
    public function snapshot(CashSession $session): array
    {
        $sedeId    = $session->sede_id;
        $userId    = $session->user_id;
        $sessionId = $session->id;
        $openedAt  = $session->opened_at;

        // ── Opening amount ────────────────────────────────────────────────────
        $opening = (float) $session->opening_amount;

        // ── Sales in this session ─────────────────────────────────────────────
        $ventasEfectivo = (float) Sale::where('cash_session_id', $sessionId)
            ->where('estado', 'completada')
            ->sum('total_sistema');

        // ── Approved outflow movements (reduce physical cash) ─────────────────
        // deposito = operator sent cash to safe/bank
        // retiro, pago_proveedor, envio_boveda, gasto_operativo = other outflows
        $egresos = (float) CashMovement::where('cash_session_id', $sessionId)
            ->where('status', 'aprobado')
            ->whereIn('type', ['deposito', 'retiro', 'pago_proveedor', 'envio_boveda', 'gasto_operativo'])
            ->sum('amount');

        // ── Approved inflow movements (add to physical cash) ──────────────────
        // cambio = incoming float/change fund from management
        $ingresos = (float) CashMovement::where('cash_session_id', $sessionId)
            ->where('status', 'aprobado')
            ->whereIn('type', ['cambio'])
            ->sum('amount');

        // ── Approved courtesies (value of products given free) ────────────────
        $valorCortesias = (float) CourtesyTransaction::where('courtesy_transactions.sede_id', $sedeId)
            ->where('courtesy_transactions.user_id', $userId)
            ->where('courtesy_transactions.status', 'aprobado')
            ->where('courtesy_transactions.created_at', '>=', $openedAt)
            ->join('products', 'products.id', '=', 'courtesy_transactions.product_id')
            ->sum(DB::raw('courtesy_transactions.quantity * products.precio_venta'));

        // ── Pending counts (for operator awareness) ───────────────────────────
        $pendingMovimientos = CashMovement::where('cash_session_id', $sessionId)
            ->where('status', 'pendiente')
            ->count();

        $pendingCortesias = CourtesyTransaction::where('courtesy_transactions.sede_id', $sedeId)
            ->where('courtesy_transactions.user_id', $userId)
            ->where('courtesy_transactions.status', 'pendiente')
            ->where('courtesy_transactions.created_at', '>=', $openedAt)
            ->count();

        // ── Last approved outflow ─────────────────────────────────────────────
        $lastEgreso = CashMovement::where('cash_session_id', $sessionId)
            ->where('status', 'aprobado')
            ->whereIn('type', ['deposito', 'retiro', 'envio_boveda'])
            ->latest('approved_at')
            ->first();

        // ── Total in box ──────────────────────────────────────────────────────
        $total = $opening + $ventasEfectivo + $ingresos - $egresos - $valorCortesias;

        // ── Status classification ─────────────────────────────────────────────
        $status = match(true) {
            $total < 0     => 'negativa',
            $total < 50    => 'baja',
            default        => 'ok',
        };

        return [
            'opening'            => $opening,
            'ventas_efectivo'    => $ventasEfectivo,
            'ingresos'           => $ingresos,
            'egresos'            => $egresos,
            'valor_cortesias'    => $valorCortesias,
            'total'              => $total,
            'status'             => $status,
            'pending_mov'        => $pendingMovimientos,
            'pending_cor'        => $pendingCortesias,
            'last_egreso'        => $lastEgreso,
            'session_duration'   => $session->duration,
            'opened_at'          => $session->opened_at,
        ];
    }
}
