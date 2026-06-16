<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\Sede;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

class CashMovementController extends Controller
{
    // ── OPERATOR: show create form ────────────────────────────────────────────

    public function create()
    {
        abort_unless(auth()->user()->can('cash_movements.create'), 403);

        $activeSession = auth()->user()->sede_id
            ? CashSession::activeForUser(auth()->id(), auth()->user()->sede_id)
            : null;

        return view('operator.cash-movement-create', compact('activeSession'));
    }

    // ── OPERATOR: store movement ──────────────────────────────────────────────

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('cash_movements.create'), 403);

        if (auth()->user()->sede_id === null) {
            return back()->withErrors(['sede_id' => 'Necesitas sede asignada para registrar movimientos de caja.']);
        }

        $validated = $request->validate([
            'type'           => 'required|in:deposito,retiro,pago_proveedor,envio_boveda,cambio,gasto_operativo,ajuste,diferencia',
            'amount'         => 'required|numeric|min:0.01',
            'motivo'         => 'required|string|max:500',
            'observaciones'  => 'nullable|string|max:1000',
            'attachment'     => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $attachmentPath = $request->file('attachment')->store('cash_movements', 'public');

        $activeSession = CashSession::activeForUser(auth()->id(), auth()->user()->sede_id);

        $movement = CashMovement::create([
            'sede_id'         => auth()->user()->sede_id,
            'user_id'         => auth()->id(),
            'cash_session_id' => $activeSession?->id,
            'type'            => $validated['type'],
            'amount'          => $validated['amount'],
            'motivo'          => $validated['motivo'],
            'observaciones'   => $validated['observaciones'] ?? null,
            'attachment_path' => $attachmentPath,
            'status'          => 'pendiente',
        ]);

        ActivityLogger::log(
            'caja.movimiento.registrado',
            'Movimiento de caja registrado: ' . CashMovement::typeLabel($movement->type) .
            ' $' . number_format($movement->amount, 2) . ' — ' . $movement->motivo .
            (auth()->user()->sede ? ' · ' . auth()->user()->sede->nombre : ''),
            $movement
        );

        return redirect()->route('dashboard')
            ->with('success', 'Movimiento registrado. Pendiente de aprobación por supervisión.');
    }

    // ── ADMIN: list movements ─────────────────────────────────────────────────

    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('cash_movements.approve'), 403);

        $status   = $request->get('status', 'pendiente');
        $sedeId   = $request->get('sede_id');
        $userId   = $request->get('user_id');
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');

        $movements = CashMovement::with('sede', 'user', 'cashSession')
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when($sedeId, fn($q) => $q->where('sede_id', $sedeId))
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($dateFrom, fn($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $pendingCount  = CashMovement::where('status', 'pendiente')->count();
        $approvedCount = CashMovement::where('status', 'aprobado')->count();
        $rejectedCount = CashMovement::where('status', 'rechazado')->count();

        $sedes     = Sede::where('activa', true)->orderBy('nombre')->get();
        $operators = User::whereHas('roles', fn($q) => $q->whereIn('name', ['operador', 'supervisor']))
            ->orderBy('name')->get();

        return view('admin.cash-movements.index', compact(
            'movements', 'status', 'pendingCount', 'approvedCount', 'rejectedCount',
            'sedes', 'operators'
        ));
    }

    // ── ADMIN: show + approval form ───────────────────────────────────────────

    public function show(CashMovement $movement)
    {
        abort_unless(auth()->user()->can('cash_movements.approve'), 403);

        $movement->load('sede', 'user', 'cashSession', 'approvedBy');

        return view('admin.cash-movements.show', compact('movement'));
    }

    // ── ADMIN: approve ────────────────────────────────────────────────────────

    public function approve(CashMovement $movement)
    {
        abort_unless(auth()->user()->can('cash_movements.approve'), 403);

        if (! $movement->isPending()) {
            return back()->with('error', 'Este movimiento ya fue procesado.');
        }

        if ($movement->isDeferredPayment()) {
            return back()->with('error', 'Este pago fue diferido automáticamente y se asignará a la siguiente sesión de caja. No puede procesarse manualmente.');
        }

        $movement->update([
            'status'      => 'aprobado',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        ActivityLogger::log(
            'caja.movimiento.aprobado',
            'Movimiento aprobado: ' . CashMovement::typeLabel($movement->type) .
            ' $' . number_format($movement->amount, 2) . ' — ' . $movement->motivo .
            ($movement->sede ? ' · ' . $movement->sede->nombre : ''),
            $movement
        );

        return redirect()->route('cash-movements.index')
            ->with('success', 'Movimiento aprobado correctamente.');
    }

    // ── ADMIN: reject ─────────────────────────────────────────────────────────

    public function reject(Request $request, CashMovement $movement)
    {
        abort_unless(auth()->user()->can('cash_movements.approve'), 403);

        if (! $movement->isPending()) {
            return back()->with('error', 'Este movimiento ya fue procesado.');
        }

        if ($movement->isDeferredPayment()) {
            return back()->with('error', 'Este pago fue diferido automáticamente y se asignará a la siguiente sesión de caja. No puede rechazarse manualmente.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $movement->update([
            'status'           => 'rechazado',
            'approved_by'      => auth()->id(),
            'approved_at'      => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        ActivityLogger::log(
            'caja.movimiento.rechazado',
            'Movimiento rechazado: ' . CashMovement::typeLabel($movement->type) .
            ' $' . number_format($movement->amount, 2) .
            ($movement->sede ? ' · ' . $movement->sede->nombre : ''),
            $movement
        );

        return redirect()->route('cash-movements.index')
            ->with('warning', 'Movimiento rechazado.');
    }
}
