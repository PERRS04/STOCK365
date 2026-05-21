<?php

namespace App\Http\Controllers;

use App\Models\InventoryReceipt;
use App\Models\Provider;
use App\Models\Sede;
use Illuminate\Http\Request;

class PurchasesController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->can('receipts.approve'), 403);

        $query = InventoryReceipt::with('sede', 'user', 'provider', 'items.product')
            ->where('estado', 'aprobado');

        if ($request->filled('sede_id')) {
            $query->where('sede_id', $request->sede_id);
        }

        if ($request->filled('provider_id')) {
            $query->where('provider_id', $request->provider_id);
        }

        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->desde);
        }

        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->hasta);
        }

        $purchases = $query->latest()->paginate(20)->withQueryString();

        $totalMes   = InventoryReceipt::where('estado', 'aprobado')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('monto_pagado');

        $totalAnio  = InventoryReceipt::where('estado', 'aprobado')
            ->whereYear('created_at', now()->year)
            ->sum('monto_pagado');

        $sedes     = Sede::orderBy('nombre')->get();
        $providers = Provider::where('activo', true)->orderBy('nombre')->get();

        return view('admin.purchases.index', compact(
            'purchases', 'totalMes', 'totalAnio', 'sedes', 'providers'
        ));
    }
}
