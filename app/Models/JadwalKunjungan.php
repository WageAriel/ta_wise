<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JadwalKunjungan extends Model
{
    use HasFactory;

    protected $table = 'jadwal_kunjungan';

    protected $fillable = [
        'id_klasifikasi',
        'id_user_petugas',
        'tanggal_kunjungan',
        'waktu_kunjungan',
        'status',
    ];

    public function klasifikasi()
    {
        return $this->belongsTo(Klasifikasi::class, 'id_klasifikasi', 'id_klasifikasi');
    }

    public function petugas()
    {
        return $this->belongsTo(User::class, 'id_user_petugas', 'id');
    }
}
