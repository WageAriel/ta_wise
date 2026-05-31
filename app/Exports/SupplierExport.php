<?php

namespace App\Exports;

use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SupplierExport implements FromCollection, WithHeadings, WithMapping
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
}
