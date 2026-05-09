<?php

namespace App\Http\Controllers;

use App\Models\CashClosing;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashClosingController extends Controller
{
    public function create()
    {
        $user = Auth::user();
        $sede = $user->sede;

        $today = now()->toDateString();
        $sales = Sale::where('sede_id', $sede->id)
            ->whereDate('fecha_venta', $today)
            ->get();

        $totalSistema = $sales->sum('total_sistema');

        return view('operator.cash-closing', [
            'sede' => $sede,
            'totalSistema' => $totalSistema,
            'sales' => $sales,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'efectivo' => 'required|numeric|min:0',
            'transferencias' => 'nullable|numeric|min:0',
            'cheques' => 'nullable|numeric|min:0',
            'observaciones' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $user = Auth::user();
            $sede = $user->sede;
            $today = now()->toDateString();

            $sales = Sale::where('sede_id', $sede->id)
                ->whereDate('fecha_venta', $today)
                ->get();

            $totalSistema = $sales->sum('total_sistema');
            $totalReportado = $validated['efectivo'] + ($validated['transferencias'] ?? 0) + ($validated['cheques'] ?? 0);
            $diferencia = $totalReportado - $totalSistema;

            $cashClosing = CashClosing::create([
                'sede_id' => $sede->id,
                'user_id' => Auth::id(),
                'fecha_cierre' => now(),
                'total_sistema' => $totalSistema,
                'efectivo' => $validated['efectivo'],
                'transferencias' => $validated['transferencias'] ?? 0,
                'cheques' => $validated['cheques'] ?? 0,
                'diferencia' => $diferencia,
                'observaciones' => $validated['observaciones'] ?? null,
                'estado' => 'pendiente',
            ]);

            return redirect()->route('operator.dashboard')
                ->with('success', 'Cierre de caja registrado. Pendiente de aprobación del BOSS.');
        });
    }

    public function approvalsIndex()
    {
        $this->authorize('boss');

        $closings = CashClosing::where('estado', 'pendiente')
            ->with('sede', 'user')
            ->latest('fecha_cierre')
            ->paginate(20);

        return view('admin.cash-closings.pending', ['closings' => $closings]);
    }

    public function approve(CashClosing $closing)
    {
        $this->authorize('boss');

        $closing->update([
            'estado' => 'aprobado',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return redirect()->back()
            ->with('success', 'Cierre de caja aprobado correctamente');
    }

    public function reject(Request $request, CashClosing $closing)
    {
        $this->authorize('boss');

        $closing->update([
            'estado' => 'rechazado',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
        ]);

        return redirect()->back()
            ->with('success', 'Cierre de caja rechazado');
    }
}
