<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierScore extends Model
{
    protected $fillable = [
        'supplier_id', 'admin_id', 'tahun_periode', 
        'skor_kelengkapan_dokumen', 'skor_nib', 'skor_npwp',
        'skor_akta_pendirian', 'skor_izin_usaha', 'skor_izin_khusus', 'skor_sk_domisili','skor_laporan_keuangan',
        'total_skor', 'catatan'
    ];
}

