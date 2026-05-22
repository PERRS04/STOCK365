<?php

namespace App\Http\Controllers;

use App\Models\CashClosing;
use App\Models\CashSession;
use App\Models\Sale;
use App\Services\ActivityLogger;
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

        // Warn if a closing already exists for today
        $existingClosing = CashClosing::where('sede_id', $sede->id)
            ->whereDate('fecha_cierre', $today)
            ->whereIn('estado', ['pendiente', 'aprobado'])
            ->first();

        $sales        = Sale::with('items')->where('sede_id', $sede->id)->whereDate('fecha_venta', $today)->get();
        $totalSistema = $sales->sum('total_sistema');

        return view('operator.cash-closing', compact('sede', 'totalSistema', 'sales', 'existingClosing'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('sales.create'), 403);

        $validated = $request->validate([
            'monto_inicial'  => 'required|numeric|min:0',
            'efectivo'       => 'required|numeric|min:0',
            'transferencias' => 'nullable|numeric|min:0',
            'cheques'        => 'nullable|numeric|min:0',
            'observaciones'  => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($validated) {
            $user  = Auth::user();
            $sede  = $user->sede;
            $today = now()->toDateString();

            // Prevent duplicate closings
            $existing = CashClosing::where('sede_id', $sede->id)
                ->whereDate('fecha_cierre', $today)
                ->whereIn('estado', ['pendiente', 'aprobado'])
                ->exists();

            if ($existing) {
                return redirect()->back()
                    ->with('warning', 'Ya existe un cierre de caja registrado para hoy.');
            }

            $sales        = Sale::where('sede_id', $sede->id)->whereDate('fecha_venta', $today)->get();
            $totalSistema = $sales->sum('total_sistema');

            $totalReportado = ($validated['efectivo'])
                + ($validated['transferencias'] ?? 0)
                + ($validated['cheques'] ?? 0);
            $diferencia = $totalReportado - $totalSistema;

            $cashSession = CashSession::activeForUser($user->id, $sede->id);

            $closing = CashClosing::create([
                'sede_id'         => $sede->id,
                'user_id'         => $user->id,
                'cash_session_id' => $cashSession?->id,
                'monto_inicial'   => $validated['monto_inicial'],
                'fecha_cierre'    => now(),
                'total_sistema'   => $totalSistema,
                'efectivo'        => $validated['efectivo'],
                'transferencias'  => $validated['transferencias'] ?? 0,
                'cheques'         => $validated['cheques'] ?? 0,
                'diferencia'      => $diferencia,
                'observaciones'   => $validated['observaciones'] ?? null,
                'estado'          => 'pendiente',
            ]);

            if ($cashSession) {
                $cashSession->update(['status' => 'pending_closing']);
            }

            ActivityLogger::log(
                'closing.create',
                "Cierre registrado — {$sede->nombre} | Sistema: \${$totalSistema} | Reportado: \${$totalReportado} | Diferencia: \${$diferencia}",
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

        $closing->update([
            'estado'      => 'aprobado',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        if ($closing->cashSession && $closing->cashSession->isPendingClosing()) {
            $closing->cashSession->update(['status' => 'closed', 'closed_at' => now()]);
        }

        ActivityLogger::log(
            'closing.approve',
            "Cierre aprobado — " . ($closing->sede?->nombre ?? '—') . " | Total: \${$closing->total_sistema} | Diferencia: \${$closing->diferencia}",
            $closing,
            ['estado' => 'pendiente'],
            ['estado' => 'aprobado']
        );

        return redirect()->back()->with('success', 'Cierre de caja aprobado correctamente');
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
            "Cierre rechazado — " . ($closing->sede?->nombre ?? '—') . " | Total: \${$closing->total_sistema}",
            $closing,
            ['estado' => 'pendiente'],
            ['estado' => 'rechazado']
        );

        return redirect()->back()->with('success', 'Cierre de caja rechazado');
    }
}
