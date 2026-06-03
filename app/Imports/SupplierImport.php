<?php

namespace App\Imports;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SupplierImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        if (empty($row['email_perusahaan'])) {
            return null;
        }

        // Tambah Data Baru
        $user = User::firstOrCreate(
            ['email' => $row['email_perusahaan']],
            [
                'username' => Str::slug($row['nama_perusahaan'] ?? 'supplier-' . rand(100, 999)),
                'password' => bcrypt('password123'),
                'role' => 'supplier',
                'is_active' => true,
            ]
        );

        return new Supplier([
            'user_id' => $user->id,
            'nama_perusahaan' => $row['nama_perusahaan'] ?? null,
            'no_telp_perusahaan' => $row['no_telp_perusahaan'] ?? null,
            'alamat_perusahaan' => $row['alamat_perusahaan'] ?? null,
            'email_perusahaan' => $row['email_perusahaan'] ?? null,
            'nama_pic' => $row['nama_pic'] ?? null,
            'no_telp_pic' => $row['no_telp_pic'] ?? null,
            'status' => $row['status'] ?? 'approved', // default approved saat import
            'tahun_periode' => $row['tahun_periode'] ?? date('Y'),
        ]);
    }
}
