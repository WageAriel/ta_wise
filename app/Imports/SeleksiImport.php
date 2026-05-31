<?php

namespace App\Imports;

use App\Models\Seleksi;
use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SeleksiImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty($row['nama_perusahaan'])) {
            return null;
        }

        $supplier = Supplier::where('nama_perusahaan', $row['nama_perusahaan'])->first();
        
        if (!$supplier) {
            return null;
        }

        return new Seleksi([
            'id_supplier' => $supplier->id,
            'id_user' => $supplier->user_id,
            'tanggal' => $row['tanggal'] ?? date('Y-m-d'),
            'status_seleksi' => $row['status_seleksi'] ?? 'Menunggu Validasi',
            'total_nilai' => $row['total_nilai'] ?? 0,
            'id_soal' => 1, // Default id_soal, bisa di-adjust sesuai kebutuhan
        ]);
    }
}
