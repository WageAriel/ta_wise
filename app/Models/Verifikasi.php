<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Verifikasi extends Model
{
    use HasFactory;

    protected $table = 'verifikasi';
    protected $primaryKey = 'id_verifikasi';

    protected $fillable = [
        'total_nilai',
        'status',
        'tanggal',
        'rekomendasi_sistem',
        'keputusan_admin',
        'id_klasifikasi',
        'id_user_admin',
        'id_user_petugas',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function klasifikasi()
    {
        return $this->belongsTo(Klasifikasi::class, 'id_klasifikasi', 'id_klasifikasi');
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'id_user_admin', 'id');
    }

    public function petugas()
    {
        return $this->belongsTo(User::class, 'id_user_petugas', 'id');
    }

    public function jawabanKlasifikasis()
    {
        return $this->hasMany(JawabanKlasifikasi::class, 'id_verifikasi', 'id_verifikasi');
    }
}
