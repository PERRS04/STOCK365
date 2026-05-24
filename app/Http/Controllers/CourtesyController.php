<?php

namespace App\Http\Controllers;

use App\Models\CourtesyTransaction;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Sede;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourtesyController extends Controller
{
    // ── OPERATOR: show create form ────────────────────────────────────────────

    public function create()
    {
        abort_unless(auth()->user()->can('courtesies.create'), 403);

        $products = Product::where('activo', true)->orderBy('nombre')->get();

        return view('operator.courtesy-create', compact('products'));
    }

    // ── OPERATOR: store courtesy ──────────────────────────────────────────────

    public function store(Request $request)
    {
        abort_unless(auth()->user()->can('courtesies.create'), 403);

        if (auth()->user()->sede_id === null) {
            return back()->withErrors(['sede_id' => 'Necesitas sede asignada para registrar cortesías.']);
        }

        $validated = $request->validate([
            'product_id'     => 'required|exists:products,id',
            'quantity'       => 'required|integer|min:1',
            'tipo'           => 'required|in:cumpleaños,apostador_vip,promocion,gerencia,incidencia,otro',
            'motivo'         => 'required|string|max:500',
            'cliente_nombre' => 'nullable|string|max:255',
            'observaciones'  => 'nullable|string|max:1000',
            'attachment'     => 'required|file|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $attachmentPath = $request->file('attachment')->store('courtesies', 'public');

        $courtesy = CourtesyTransaction::create([
            'sede_id'         => auth()->user()->sede_id,
            'user_id'         => auth()->id(),
            'product_id'      => $validated['product_id'],
            'quantity'        => $validated['quantity'],
            'tipo'            => $validated['tipo'],
            'motivo'          => $validated['motivo'],
            'cliente_nombre'  => $validated['cliente_nombre'] ?? null,
            'observaciones'   => $validated['observaciones'] ?? null,
            'attachment_path' => $attachmentPath,
            'status'          => 'pendiente',
        ]);

        ActivityLogger::log(
            'cortesia.registrada',
            "Cortesía registrada: {$courtesy->product->nombre} × {$courtesy->quantity} — {$courtesy->tipo}" .
            (auth()->user()->sede ? ' · ' . auth()->user()->sede->nombre : ''),
            $courtesy
        );

        return redirect()->route('dashboard')
            ->with('success', 'Cortesía registrada. Pendiente de aprobación por supervisión.');
    }

    // ── ADMIN: list courtesies ────────────────────────────────────────────────

    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('courtesies.approve'), 403);

        $status   = $request->get('status', 'pendiente');
        $sedeId   = $request->get('sede_id');
        $userId   = $request->get('user_id');
        $dateFrom = $request->get('date_from');
        $dateTo   = $request->get('date_to');

        $courtesies = CourtesyTransaction::with('sede', 'user', 'product')
            ->when($status !== 'all', fn($q) => $q->where('status', $status))
            ->when($sedeId, fn($q) => $q->where('sede_id', $sedeId))
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($dateFrom, fn($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $pendingCount  = CourtesyTransaction::where('status', 'pendiente')->count();
        $approvedCount = CourtesyTransaction::where('status', 'aprobado')->count();
        $rejectedCount = CourtesyTransaction::where('status', 'rechazado')->count();

        $sedes     = Sede::where('activa', true)->orderBy('nombre')->get();
        $operators = User::whereHas('roles', fn($q) => $q->whereIn('name', ['operador', 'supervisor']))
            ->orderBy('name')->get();

        return view('admin.courtesies.index', compact(
            'courtesies', 'status', 'pendingCount', 'approvedCount', 'rejectedCount',
            'sedes', 'operators'
        ));
    }

    // ── ADMIN: show + approval form ───────────────────────────────────────────

    public function show(CourtesyTransaction $courtesy)
    {
        abort_unless(auth()->user()->can('courtesies.approve'), 403);

        $courtesy->load('sede', 'user', 'product', 'approvedBy');

        $inventory = Inventory::where('product_id', $courtesy->product_id)
            ->where('sede_id', $courtesy->sede_id)
            ->first();

        return view('admin.courtesies.show', compact('courtesy', 'inventory'));
    }

    // ── ADMIN: approve + deduct stock ─────────────────────────────────────────

    public function approve(CourtesyTransaction $courtesy)
    {
        abort_unless(auth()->user()->can('courtesies.approve'), 403);

        if (! $courtesy->isPending()) {
            return back()->with('error', 'Esta cortesía ya fue procesada.');
        }

        try {
            DB::transaction(function () use ($courtesy) {
                $inventory = Inventory::where('product_id', $courtesy->product_id)
                    ->where('sede_id', $courtesy->sede_id)
                    ->first();

                if (! $inventory || $inventory->cantidad_stock < $courtesy->quantity) {
                    throw new \Exception(
                        'Stock insuficiente. Disponible: ' .
                        ($inventory?->cantidad_stock ?? 0) .
                        ' uds — Solicitado: ' . $courtesy->quantity . ' uds.'
                    );
                }

                $inventory->update([
                    'cantidad_stock'       => $inventory->cantidad_stock - $courtesy->quantity,
                    'ultima_actualizacion' => now(),
                ]);

                InventoryMovement::create([
                    'product_id'       => $courtesy->product_id,
                    'sede_id'          => $courtesy->sede_id,
                    'tipo'             => 'cortesia',
                    'cantidad'         => $courtesy->quantity,
                    'motivo'           => 'Cortesía: ' . CourtesyTransaction::tipoLabel($courtesy->tipo) . ' — ' . $courtesy->motivo,
                    'reference_id'     => $courtesy->id,
                    'reference_type'   => 'courtesy',
                    'user_id'          => auth()->id(),
                    'fecha_movimiento' => now(),
                ]);

                $courtesy->update([
                    'status'      => 'aprobado',
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);

                ActivityLogger::log(
                    'cortesia.aprobada',
                    "Cortesía aprobada: {$courtesy->product->nombre} × {$courtesy->quantity} — {$courtesy->tipo}" .
                    ($courtesy->sede ? ' · ' . $courtesy->sede->nombre : ''),
                    $courtesy
                );
            });
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('courtesies.index')
            ->with('success', 'Cortesía aprobada. Stock descontado correctamente.');
    }

    // ── ADMIN: reject ─────────────────────────────────────────────────────────

    public function reject(Request $request, CourtesyTransaction $courtesy)
    {
        abort_unless(auth()->user()->can('courtesies.approve'), 403);

        if (! $courtesy->isPending()) {
            return back()->with('error', 'Esta cortesía ya fue procesada.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $courtesy->update([
            'status'           => 'rechazado',
            'approved_by'      => auth()->id(),
            'approved_at'      => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        ActivityLogger::log(
            'cortesia.rechazada',
            "Cortesía rechazada: {$courtesy->product->nombre} × {$courtesy->quantity}" .
            ($courtesy->sede ? ' · ' . $courtesy->sede->nombre : ''),
            $courtesy
        );

        return redirect()->route('courtesies.index')
            ->with('warning', 'Cortesía rechazada.');
    }
}
