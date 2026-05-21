<?php

namespace App\Exports;

use App\Models\Sale;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalesExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithChunkReading
{
    public function query()
    {
        return Sale::with('sede', 'user', 'items')->latest('fecha_venta');
    }

    public function map($sale): array
    {
        return [
            $sale->id,
            $sale->sede->nombre ?? '—',
            $sale->user->name ?? '(usuario eliminado)',
            $sale->fecha_venta->format('Y-m-d H:i'),
            number_format((float)$sale->subtotal_bruto, 2),
            number_format((float)$sale->descuento, 2),
            number_format((float)$sale->total_sistema, 2),
            $sale->items->count(),
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Sede',
            'Operador',
            'Fecha/Hora',
            'Subtotal Bruto (USD)',
            'Descuento (USD)',
            'Total Neto (USD)',
            'Items',
        ];
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '003594']],
            ],
        ];
    }
}
