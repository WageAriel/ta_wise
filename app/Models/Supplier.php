<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'nama_perusahaan', 'no_telp_perusahaan', 'alamat_perusahaan', 'email_perusahaan',
        'nama_pic', 'no_telp_pic', 'email_pic', 'nama_bank', 'no_rekening', 'atas_nama',
        'tahun_periode', 'status', 'catatan_admin', 'submitted_at', 'reviewed_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];
    // relasi
    public function user() { return $this->belongsTo(User::class); }
    public function documents() { return $this->hasMany(SupplierDocument::class); }
    public function scores() { return $this->hasMany(SupplierScore::class); } // diganti hasMany agar menyimpan histori skor tiap tahun
}

