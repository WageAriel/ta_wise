<?php

namespace App\Exports;

use App\Models\Seleksi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SeleksiExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Seleksi::with('supplier')->get();
    }

    public function headings(): array
    {
        return [
            'ID Seleksi',
            'Nama Perusahaan',
            'Tanggal',
            'Status Seleksi',
            'Total Nilai',
        ];
    }

    public function map($seleksi): array
    {
        return [
            $seleksi->id_seleksi,
            $seleksi->supplier->nama_perusahaan ?? 'N/A',
            $seleksi->tanggal,
            $seleksi->status_seleksi,
            $seleksi->total_nilai,
        ];
    }
}
