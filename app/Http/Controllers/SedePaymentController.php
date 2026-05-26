<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\ReceiptPaymentAllocation;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SedePaymentController extends Controller
{
    // ── List pending allocations assigned to current operator's sede ──────────

    public function index()
    {
        abort_unless(auth()->user()->sede_id !== null, 403);

        $allocations = ReceiptPaymentAllocation::where('source_sede_id', auth()->user()->sede_id)
            ->where('status', 'pending')
            ->with(['receipt' => fn($q) => $q->with('sede', 'user')])
            ->latest()
            ->get();

        return view('operator.sede-payments-pending', compact('allocations'));
    }

    // ── Show confirmation form for one allocation ─────────────────────────────

    public function showConfirm(ReceiptPaymentAllocation $allocation)
    {
        abort_unless(auth()->user()->sede_id === $allocation->source_sede_id, 403);
        abort_unless($allocation->isPending(), 422);

        $allocation->load('receipt.sede', 'receipt.user');

        return view('operator.sede-payment-confirm', compact('allocation'));
    }

    // ── Process confirmation: upload evidence + deduct cash ───────────────────

    public function confirm(Request $request, ReceiptPaymentAllocation $allocation)
    {
        abort_unless(auth()->user()->sede_id === $allocation->source_sede_id, 403);
        abort_unless($allocation->isPending(), 422, 'Este pago ya fue procesado.');

        $validated = $request->validate([
            'evidence_file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:5120',
            'notes'         => 'nullable|string|max:500',
        ]);

        $activeSession = CashSession::where('sede_id', auth()->user()->sede_id)
            ->whereIn('status', ['open', 'pending_closing'])
            ->latest('opened_at')
            ->first();

        if (! $activeSession) {
            return back()->withErrors([
                'error' => 'Necesitas tener caja abierta para confirmar el pago.',
            ]);
        }

        $evidencePath = $request->file('evidence_file')
            ->store('sede-payments/' . date('Y/m'), 'public');

        $allocation->load('receipt.sede');

        DB::transaction(function () use ($allocation, $activeSession, $evidencePath, $validated) {
            $movement = CashMovement::create([
                'sede_id'         => auth()->user()->sede_id,
                'user_id'         => auth()->id(),
                'cash_session_id' => $activeSession->id,
                'type'            => 'pago_proveedor',
                'amount'          => $allocation->amount,
                'motivo'          => "Aporte pago proveedor: {$allocation->receipt->supplier_name}",
                'observaciones'   => "Recepción #{$allocation->inventory_receipt_id} — {$allocation->receipt->sede->nombre}",
                'attachment_path' => $evidencePath,
                'status'          => 'aprobado',
                'approved_by'     => auth()->id(),
                'approved_at'     => now(),
            ]);

            $allocation->update([
                'status'           => 'confirmed',
                'evidence_path'    => $evidencePath,
                'confirmed_by'     => auth()->id(),
                'confirmed_at'     => now(),
                'cash_movement_id' => $movement->id,
                'notes'            => $validated['notes'] ?? null,
            ]);

            ActivityLogger::log(
                'sede_payment.confirmed',
                "Pago de apoyo confirmado: {$allocation->receipt->supplier_name} · \${$allocation->amount} · Recepción #{$allocation->inventory_receipt_id}",
                $allocation
            );
        });

        return redirect()->route('sede-payments.pending')
            ->with('success', 'Pago confirmado. $' . number_format($allocation->amount, 2) . ' descontado de caja.');
    }
}
