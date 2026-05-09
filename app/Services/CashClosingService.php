<?php

namespace App\Services;

use App\Models\CashClosing;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class CashClosingService
{
    public function createClosing(array $data)
    {
        return DB::transaction(function () use ($data) {
            $sales = Sale::where('sede_id', $data['sede_id'])
                ->whereDate('fecha_venta', now())
                ->get();

            $totalSistema = $sales->sum('total_sistema');
            $totalReportado = $data['efectivo'] + ($data['transferencias'] ?? 0) + ($data['cheques'] ?? 0);
            $diferencia = $totalReportado - $totalSistema;

            return CashClosing::create([
                'sede_id' => $data['sede_id'],
                'user_id' => $data['user_id'],
                'fecha_cierre' => now(),
                'total_sistema' => $totalSistema,
                'efectivo' => $data['efectivo'],
                'transferencias' => $data['transferencias'] ?? 0,
                'cheques' => $data['cheques'] ?? 0,
                'diferencia' => $diferencia,
                'observaciones' => $data['observaciones'] ?? null,
                'estado' => 'pendiente',
            ]);
        });
    }

    public function approveClosure(CashClosing $closing, int $bosId): void
    {
        $closing->update([
            'estado' => 'aprobado',
            'approved_by' => $bosId,
            'approved_at' => now(),
        ]);
    }

    public function rejectClosure(CashClosing $closing, int $bossId): void
    {
        $closing->update([
            'estado' => 'rechazado',
            'approved_by' => $bossId,
            'approved_at' => now(),
        ]);
    }
}
