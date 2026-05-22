<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        return view('operator.cash-session-open', ['sede' => $user->sede]);
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

        $session = CashSession::create([
            'user_id'        => $user->id,
            'sede_id'        => $user->sede_id,
            'opening_amount' => $validated['opening_amount'],
            'notes'          => $validated['notes'] ?? null,
            'status'         => 'open',
            'opened_at'      => now(),
        ]);

        ActivityLogger::log(
            'cash_session.open',
            "Caja abierta — " . ($user->sede?->nombre ?? '—') . " | Monto inicial: \${$session->opening_amount}",
            $session,
            [],
            ['opening_amount' => $session->opening_amount]
        );

        return redirect()->route('pos.create')
            ->with('success', 'Caja abierta correctamente. ¡Listo para vender!');
    }

    public function status()
    {
        abort_unless(auth()->user()->isOperator(), 403);

        $user    = Auth::user();
        $session = CashSession::where('user_id', $user->id)
            ->where('sede_id', $user->sede_id)
            ->whereIn('status', ['open', 'pending_closing'])
            ->with('sales')
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
