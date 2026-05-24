<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JawabanKlasifikasi extends Model
{
    use HasFactory;

    protected $table = 'jawaban_klasifikasi';
    protected $primaryKey = 'id_jawaban';

    protected $fillable = [
        'jawaban_verifikasi',
        'catatan_verifikasi',
        'id_klasifikasi',
        'id_pertanyaan',
        'id_opsi',
        'id_opsi_verifikasi',
        'id_verifikasi',
    ];

    public function klasifikasi()
    {
        return $this->belongsTo(Klasifikasi::class, 'id_klasifikasi', 'id_klasifikasi');
    }

    public function pertanyaan()
    {
        return $this->belongsTo(Pertanyaan::class, 'id_pertanyaan', 'id_pertanyaan');
    }

    public function opsi()
    {
        return $this->belongsTo(Opsi::class, 'id_opsi', 'id_opsi');
    }

    // Opsi yang dipilih petugas saat verifikasi
    public function opsiVerifikasi()
    {
        return $this->belongsTo(Opsi::class, 'id_opsi_verifikasi', 'id_opsi');
    }

    public function verifikasi()
    {
        return $this->belongsTo(Verifikasi::class, 'id_verifikasi', 'id_verifikasi');
    }
}
