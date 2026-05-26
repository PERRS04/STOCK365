<?php

namespace App\Livewire;

use App\Models\CashMovement;
use App\Models\CashSession;
use App\Models\CourtesyTransaction;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CashTimeline extends Component
{
    public function render()
    {
        $user = Auth::user();

        if (!$user?->sede_id) {
            return view('livewire.cash-timeline', ['events' => collect(), 'session' => null]);
        }

        $session = CashSession::where('user_id', $user->id)
            ->where('sede_id', $user->sede_id)
            ->whereIn('status', ['open', 'pending_closing'])
            ->first();

        if (!$session) {
            return view('livewire.cash-timeline', ['events' => collect(), 'session' => null]);
        }

        $debitTypes = ['deposito', 'retiro', 'pago_proveedor', 'envio_boveda', 'gasto_operativo'];

        $salesEvents = Sale::where('cash_session_id', $session->id)
            ->where('estado', 'completada')
            ->withCount('items')
            ->latest('fecha_venta')
            ->limit(20)
            ->get()
            ->map(fn ($s) => [
                'type'   => 'venta',
                'time'   => $s->fecha_venta,
                'label'  => 'Venta',
                'amount' => (float) $s->total_sistema,
                'sign'   => '+',
                'color'  => 'emerald',
                'detail' => $s->items_count . ($s->items_count === 1 ? ' producto' : ' productos'),
            ]);

        $movEvents = CashMovement::where('cash_session_id', $session->id)
            ->where('status', 'aprobado')
            ->latest('approved_at')
            ->limit(10)
            ->get()
            ->map(fn ($m) => [
                'type'   => 'movimiento',
                'time'   => $m->approved_at,
                'label'  => CashMovement::typeLabel($m->type),
                'amount' => (float) $m->amount,
                'sign'   => in_array($m->type, $debitTypes) ? '-' : '+',
                'color'  => in_array($m->type, $debitTypes) ? 'amber' : 'teal',
                'detail' => $m->motivo,
            ]);

        $courtEvents = CourtesyTransaction::where('sede_id', $user->sede_id)
            ->where('user_id', $user->id)
            ->where('status', 'aprobado')
            ->where('created_at', '>=', $session->opened_at)
            ->with('product:id,nombre,precio_venta')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($c) => [
                'type'   => 'cortesia',
                'time'   => $c->approved_at ?? $c->created_at,
                'label'  => 'Cortesía',
                'amount' => $c->quantity * (float) ($c->product?->precio_venta ?? 0),
                'sign'   => '-',
                'color'  => 'pink',
                'detail' => $c->product?->nombre ?? '',
            ]);

        $openEvent = collect([[
            'type'   => 'apertura',
            'time'   => $session->opened_at,
            'label'  => 'Apertura de caja',
            'amount' => (float) $session->opening_amount,
            'sign'   => '',
            'color'  => 'blue',
            'detail' => 'Inicio de turno',
        ]]);

        $events = $salesEvents
            ->merge($movEvents)
            ->merge($courtEvents)
            ->merge($openEvent)
            ->sortByDesc('time')
            ->take(30)
            ->values();

        return view('livewire.cash-timeline', compact('events', 'session'));
    }
}
