<?php

namespace App\Exports;

use App\Models\Klasifikasi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class KlasifikasiExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Klasifikasi::with('supplier')->get();
    }

    public function headings(): array
    {
        return [
            'ID Klasifikasi',
            'Nama Perusahaan',
            'Tanggal',
            'Status Klasifikasi',
            'Total Nilai',
        ];
    }

    public function map($klasifikasi): array
    {
        return [
            $klasifikasi->id_klasifikasi,
            $klasifikasi->supplier->nama_perusahaan ?? 'N/A',
            $klasifikasi->tanggal ? $klasifikasi->tanggal->format('Y-m-d') : '',
            $klasifikasi->status_klasifikasi,
            $klasifikasi->total_nilai,
        ];
    }
}
