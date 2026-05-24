<?php

namespace App\Http\Controllers;

use App\Models\CashMovement;
use App\Models\CourtesyTransaction;

class ApprovalController extends Controller
{
    public function index()
    {
        abort_unless(
            auth()->user()->can('courtesies.approve') || auth()->user()->can('cash_movements.approve'),
            403
        );

        $pendingCourtesies = CourtesyTransaction::with('sede', 'user', 'product')
            ->where('status', 'pendiente')
            ->latest()
            ->get();

        $pendingCashMovements = CashMovement::with('sede', 'user')
            ->where('status', 'pendiente')
            ->latest()
            ->get();

        $totalPending = $pendingCourtesies->count() + $pendingCashMovements->count();

        return view('admin.approvals.index', compact(
            'pendingCourtesies', 'pendingCashMovements', 'totalPending'
        ));
    }
}
