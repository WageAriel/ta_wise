<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SupplierImportTemplate implements WithHeadings, WithStyles
{
    public function headings(): array
    {
        return [
            'nama_perusahaan',
            'no_telp_perusahaan',
            'alamat_perusahaan',
            'email_perusahaan',
            'nama_pic',
            'no_telp_pic',
            'email_pic',
            'nama_bank',
            'no_rekening',
            'atas_nama'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF4F46E5'] // Indigo 600
                ]
            ],
        ];
    }
}
