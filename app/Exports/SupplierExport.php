<?php

namespace App\Exports;

use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SupplierExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection()
    {
        return Supplier::all();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nama Perusahaan',
            'No Telp Perusahaan',
            'Alamat Perusahaan',
            'Email Perusahaan',
            'Nama PIC',
            'No Telp PIC',
            'Status',
            'Catatan Admin',
            'Tahun Periode'
        ];
    }

    public function map($supplier): array
    {
        return [
            $supplier->id,
            $supplier->nama_perusahaan,
            $supplier->no_telp_perusahaan,
            $supplier->alamat_perusahaan,
            $supplier->email_perusahaan,
            $supplier->nama_pic,
            $supplier->no_telp_pic,
            $supplier->status,
            $supplier->catatan_admin,
            $supplier->tahun_periode,
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
