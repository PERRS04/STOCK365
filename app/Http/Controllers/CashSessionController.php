<?php

namespace App\Http\Controllers;

use App\Models\CashClosing;
use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\CashSessionAdjustment;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashSessionController extends Controller
{
    public function create()
    {
        abort_unless(auth()->user()->isOperator(), 403);

        $user    = Auth::user();
        $session = CashSession::activeForUser($user->id, $user->sede_id);

        if ($session) {
            return redirect()->route('cash-session.status');
        }

        // Auto-inherit opening amount from last approved closing for this sede
        $lastClosing = CashClosing::where('sede_id', $user->sede_id)
            ->where('estado', 'aprobado')
            ->whereNotNull('saldo_final')
            ->orderBy('fecha_cierre', 'desc')
            ->with('approvedBy')
            ->first();

        $inheritedAmount = $lastClosing ? (float) $lastClosing->saldo_final : 0.00;

        return view('operator.cash-session-open', [
            'sede'            => $user->sede,
            'inheritedAmount' => $inheritedAmount,
            'lastClosing'     => $lastClosing,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->isOperator(), 403);

        $validated = $request->validate([
            'opening_amount' => 'required|numeric|min:0',
            'notes'          => 'nullable|string|max:500',
        ]);

        $user    = Auth::user();
        $session = CashSession::activeForUser($user->id, $user->sede_id);

        if ($session) {
            return redirect()->route('cash-session.status')
                ->with('info', 'Ya tienes una caja abierta.');
        }

        $lastClosing = CashClosing::where('sede_id', $user->sede_id)
            ->where('estado', 'aprobado')
            ->whereNotNull('saldo_final')
            ->orderBy('fecha_cierre', 'desc')
            ->first();

        $openingAmount = (float) $validated['opening_amount'];

        $session = DB::transaction(function () use ($user, $validated, $lastClosing, $openingAmount) {
            $session = CashSession::create([
                'user_id'                   => $user->id,
                'sede_id'                   => $user->sede_id,
                'opening_amount'            => $openingAmount,
                'inherited_from_closing_id' => $lastClosing?->id,
                'notes'                     => $validated['notes'] ?? null,
                'status'                    => 'open',
                'opened_at'                 => now(),
            ]);

            // ── Claim deferred supplier payments for this sede ────────────────
            $orphaned = CashMovement::whereNull('cash_session_id')
                ->where('status', 'pendiente')
                ->where('type', 'pago_proveedor')
                ->where('sede_id', $user->sede_id)
                ->lockForUpdate()
                ->get();

            foreach ($orphaned as $deferred) {
                $deferred->update([
                    'cash_session_id' => $session->id,
                    'status'          => 'aprobado',
                    'approved_by'     => $user->id,
                    'approved_at'     => now(),
                ]);

                ActivityLogger::log(
                    'receipt.payment.claimed',
                    "Pago proveedor diferido asignado a sesión #{$session->id} — {$deferred->motivo} · \${$deferred->amount}" . ($user->sede ? ' · ' . $user->sede->nombre : ''),
                    $deferred,
                    ['cash_session_id' => null, 'status' => 'pendiente'],
                    [
                        'cash_session_id'  => $session->id,
                        'cash_movement_id' => $deferred->id,
                        'sede_id'          => $user->sede_id,
                        'sede'             => $user->sede?->nombre,
                        'status'           => 'aprobado',
                        'usuario'          => $user->name,
                        'timestamp'        => now()->toDateTimeString(),
                    ]
                );
            }
            // ─────────────────────────────────────────────────────────────────

            return $session;
        });

        $inheritedAmount = $lastClosing ? (float) $lastClosing->saldo_final : 0.00;
        $source = $lastClosing
            ? ($openingAmount !== $inheritedAmount
                ? "Ajustado por operador (heredado: \${$inheritedAmount} del {$lastClosing->fecha_cierre->format('d/m/Y')})"
                : "Heredado del cierre {$lastClosing->fecha_cierre->format('d/m/Y')}")
            : 'Primera apertura';

        ActivityLogger::log(
            'cash_session.open',
            "Caja abierta — " . ($user->sede?->nombre ?? '—') . " | Apertura: \${$openingAmount} ({$source})",
            $session,
            [],
            ['opening_amount' => $openingAmount, 'source' => $source]
        );

        return redirect()->route('pos.create')
            ->with('success', 'Caja abierta. Apertura: $' . number_format($openingAmount, 2));
    }

    public function status()
    {
        abort_unless(auth()->user()->isOperator(), 403);

        $user    = Auth::user();
        $session = CashSession::where('user_id', $user->id)
            ->where('sede_id', $user->sede_id)
            ->whereIn('status', ['open', 'pending_closing'])
            ->with(['sales', 'adjustments.adjustedBy', 'inheritedFromClosing'])
            ->first();

        return view('operator.cash-session-status', [
            'session' => $session,
            'sede'    => $user->sede,
        ]);
    }

    public function index()
    {
        abort_unless(auth()->user()->isAdminLevel(), 403);

        $activeSessions = CashSession::whereIn('status', ['open', 'pending_closing'])
            ->with('user', 'sede')
            ->latest('opened_at')
            ->get();

        $recentClosed = CashSession::where('status', 'closed')
            ->with('user', 'sede')
            ->latest('closed_at')
            ->paginate(20);

        return view('admin.cash-sessions.index', compact('activeSessions', 'recentClosed'));
    }

    public function adjustOpening(Request $request, CashSession $cashSession)
    {
        abort_unless(auth()->user()->isAdminLevel(), 403);

        if ($cashSession->isClosed()) {
            return redirect()->back()->with('error', 'No se puede ajustar una sesión cerrada.');
        }

        $validated = $request->validate([
            'monto_nuevo' => 'required|numeric|min:0',
            'motivo'      => 'required|string|min:5|max:500',
        ]);

        $montoAnterior = (float) $cashSession->opening_amount;

        CashSessionAdjustment::create([
            'cash_session_id' => $cashSession->id,
            'sede_id'         => $cashSession->sede_id,
            'adjusted_by'     => Auth::id(),
            'monto_anterior'  => $montoAnterior,
            'monto_nuevo'     => $validated['monto_nuevo'],
            'motivo'          => $validated['motivo'],
        ]);

        $cashSession->update(['opening_amount' => $validated['monto_nuevo']]);

        ActivityLogger::log(
            'cash_session.adjust_opening',
            "Apertura ajustada — " . ($cashSession->sede?->nombre ?? '—') . " | Antes: \${$montoAnterior} → Después: \${$validated['monto_nuevo']} | Motivo: {$validated['motivo']}",
            $cashSession,
            ['opening_amount' => $montoAnterior],
            ['opening_amount' => $validated['monto_nuevo'], 'motivo' => $validated['motivo']]
        );

        return redirect()->back()
            ->with('success', 'Apertura ajustada a $' . number_format($validated['monto_nuevo'], 2) . '. Cambio auditado.');
    }

    public function forceClose(CashSession $cashSession)
    {
        abort_unless(auth()->user()->isAdminLevel(), 403);

        $cashSession->update([
            'status'    => 'closed',
            'closed_at' => now(),
            'notes'     => ($cashSession->notes ? $cashSession->notes . "\n" : '') . 'Cerrada forzosamente por ' . Auth::user()->name,
        ]);

        ActivityLogger::log(
            'cash_session.force_close',
            "Caja cerrada forzosamente — " . ($cashSession->sede?->nombre ?? '—') . " | Operador: " . ($cashSession->user?->name ?? '—'),
            $cashSession,
            ['status' => 'open'],
            ['status' => 'closed']
        );

        return redirect()->back()->with('success', 'Sesión de caja cerrada forzosamente.');
    }
}
