<?php

namespace App\Exports;

use App\Models\ActivityLog;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ActivityLogExport implements FromQuery, WithHeadings, WithMapping, WithStyles, WithChunkReading
{
    public function query()
    {
        return ActivityLog::with('sede')->latest();
    }

    public function map($log): array
    {
        return [
            $log->created_at->format('Y-m-d H:i:s'),
            $log->user_name,
            $log->user_role,
            $log->sede->nombre ?? '—',
            $log->action,
            $log->description,
            $log->old_values ? json_encode($log->old_values, JSON_UNESCAPED_UNICODE) : '—',
            $log->new_values ? json_encode($log->new_values, JSON_UNESCAPED_UNICODE) : '—',
            $log->ip_address ?? '—',
        ];
    }

    public function headings(): array
    {
        return ['Timestamp', 'Usuario', 'Rol', 'Sede', 'Acción', 'Descripción', 'Valores Anteriores', 'Valores Nuevos', 'IP'];
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
