<?php

namespace App\Http\Controllers;

use App\Models\CashClosing;
use App\Models\CashSession;
use App\Models\Sale;
use App\Services\ActivityLogger;
use App\Services\LiveCashBoxService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashClosingController extends Controller
{
    public function create()
    {
        abort_unless(auth()->user()->can('sales.create'), 403);

        $user  = Auth::user();
        $sede  = $user->sede;
        $today = now()->toDateString();

        $existingClosing = CashClosing::where('sede_id', $sede->id)
            ->whereDate('fecha_cierre', $today)
            ->whereIn('estado', ['pendiente', 'aprobado'])
            ->first();

        // Active session (open or awaiting approval)
        $session = CashSession::where('user_id', $user->id)
            ->where('sede_id', $sede->id)
            ->whereIn('status', ['open', 'pending_closing'])
            ->first();

        // Live snapshot for the full expected balance breakdown
        $snapshot     = null;
        $totalSistema = 0;

        if ($session) {
            $service      = new LiveCashBoxService();
            $snapshot     = $service->snapshot($session);
            $totalSistema = $snapshot['total'];
        }

        // Sales list for the detail table
        $sales = Sale::with('items')
            ->where('sede_id', $sede->id)
            ->whereDate('fecha_venta', $today)
            ->get();

        // Fallback when no session exists
        if (!$snapshot) {
            $totalSistema = $sales->sum('total_sistema');
        }

        return view('operator.cash-closing', compact(
            'sede', 'totalSistema', 'sales', 'existingClosing', 'session', 'snapshot'
        ));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('sales.create'), 403);

        $validated = $request->validate([
            'efectivo'       => 'required|numeric|min:0',
            'transferencias' => 'nullable|numeric|min:0',
            'cheques'        => 'nullable|numeric|min:0',
            'observaciones'  => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($validated) {
            $user  = Auth::user();
            $sede  = $user->sede;
            $today = now()->toDateString();

            $existing = CashClosing::where('sede_id', $sede->id)
                ->whereDate('fecha_cierre', $today)
                ->whereIn('estado', ['pendiente', 'aprobado'])
                ->exists();

            if ($existing) {
                return redirect()->back()
                    ->with('warning', 'Ya existe un cierre de caja registrado para hoy.');
            }

            // Get current session to auto-populate monto_inicial and compute live balance
            $cashSession = CashSession::where('user_id', $user->id)
                ->where('sede_id', $sede->id)
                ->whereIn('status', ['open', 'pending_closing'])
                ->first();

            // total_sistema = full live balance (opening + sales + movements - egresos - cortesias)
            if ($cashSession) {
                $service      = new LiveCashBoxService();
                $snapshot     = $service->snapshot($cashSession);
                $totalSistema = $snapshot['total'];
            } else {
                $totalSistema = Sale::where('sede_id', $sede->id)
                    ->whereDate('fecha_venta', $today)
                    ->sum('total_sistema');
            }

            $montoInicial   = $cashSession ? (float) $cashSession->opening_amount : 0;
            $totalReportado = (float) $validated['efectivo']
                + (float) ($validated['transferencias'] ?? 0)
                + (float) ($validated['cheques'] ?? 0);
            $diferencia = $totalReportado - $totalSistema;

            $closing = CashClosing::create([
                'sede_id'         => $sede->id,
                'user_id'         => $user->id,
                'cash_session_id' => $cashSession?->id,
                'monto_inicial'   => $montoInicial,
                'fecha_cierre'    => now(),
                'total_sistema'   => $totalSistema,
                'efectivo'        => $validated['efectivo'],
                'transferencias'  => $validated['transferencias'] ?? 0,
                'cheques'         => $validated['cheques'] ?? 0,
                'diferencia'      => $diferencia,
                'observaciones'   => $validated['observaciones'] ?? null,
                'estado'          => 'pendiente',
            ]);

            if ($cashSession && $cashSession->isOpen()) {
                $cashSession->update(['status' => 'pending_closing']);
            }

            ActivityLogger::log(
                'closing.create',
                "Cierre registrado — {$sede->nombre} | Esperado: \${$totalSistema} | Efectivo: \${$validated['efectivo']} | Diferencia: \${$diferencia}",
                $closing,
                [],
                ['total_sistema' => $totalSistema, 'diferencia' => $diferencia]
            );

            return redirect()->route('dashboard')
                ->with('success', 'Cierre de caja registrado. Pendiente de aprobación.');
        });
    }

    public function approvalsIndex()
    {
        abort_unless(auth()->user()->can('closings.approve'), 403);

        $closings = CashClosing::where('estado', 'pendiente')
            ->with('sede', 'user')
            ->latest('fecha_cierre')
            ->paginate(20);

        return view('admin.cash-closings.pending', compact('closings'));
    }

    public function approve(CashClosing $closing)
    {
        abort_unless(auth()->user()->can('closings.approve'), 403);

        // saldo_final = effective cash the operator physically counted
        // This amount stays in the drawer and becomes tomorrow's opening
        $saldoFinal = (float) $closing->efectivo;

        $closing->update([
            'estado'      => 'aprobado',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'saldo_final' => $saldoFinal,
        ]);

        if ($closing->cashSession && $closing->cashSession->isPendingClosing()) {
            $closing->cashSession->update(['status' => 'closed', 'closed_at' => now()]);
        }

        ActivityLogger::log(
            'closing.approve',
            "Cierre aprobado — " . ($closing->sede?->nombre ?? '—') . " | Esperado: \${$closing->total_sistema} | Efectivo: \${$closing->efectivo} | Saldo final: \${$saldoFinal}",
            $closing,
            ['estado' => 'pendiente'],
            ['estado' => 'aprobado', 'saldo_final' => $saldoFinal]
        );

        return redirect()->back()
            ->with('success', 'Cierre aprobado. Saldo final: $' . number_format($saldoFinal, 2) . ' → apertura de mañana.');
    }

    public function reject(Request $request, CashClosing $closing)
    {
        abort_unless(auth()->user()->can('closings.approve'), 403);

        $closing->update([
            'estado'      => 'rechazado',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        if ($closing->cashSession && $closing->cashSession->isPendingClosing()) {
            $closing->cashSession->update(['status' => 'open']);
        }

        ActivityLogger::log(
            'closing.reject',
            "Cierre rechazado — " . ($closing->sede?->nombre ?? '—') . " | Total esperado: \${$closing->total_sistema}",
            $closing,
            ['estado' => 'pendiente'],
            ['estado' => 'rechazado']
        );

        return redirect()->back()->with('success', 'Cierre de caja rechazado');
    }
}
