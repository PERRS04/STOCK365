<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesExport implements FromCollection, WithHeadings, WithStyles
{
    public function collection()
    {
        return Sale::with('sede', 'items')
            ->get()
            ->map(function ($sale) {
                return [
                    $sale->id,
                    $sale->sede->nombre,
                    $sale->user->name,
                    $sale->fecha_venta->format('Y-m-d H:i'),
                    $sale->total_sistema,
                    $sale->descuento,
                    $sale->total_sistema - $sale->descuento,
                    $sale->items->count(),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'ID',
            'Sede',
            'Operador',
            'Fecha/Hora',
            'Total Sistema',
            'Descuento',
            'Total Neto',
            'Items',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '003594']]],
        ];
    }
}
