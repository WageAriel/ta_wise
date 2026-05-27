<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JawabanSeleksi extends Model
{
    protected $table = 'jawaban_seleksi';
    protected $primaryKey = 'id_jawaban';
    public $timestamps = false;

    protected $fillable = [
        'jawaban',
        'id_pertanyaan',
        'id_seleksi',
    ];

    public function seleksi()
    {
        return $this->belongsTo(Seleksi::class, 'id_seleksi');
    }

    public function pertanyaan()
    {
        // Pastikan FK id_pertanyaan merujuk ke tabel pertanyaan
        return $this->belongsTo(Pertanyaan::class, 'id_pertanyaan');
    }

    public function opsi()
    {
        // Relasi ke opsi berdasarkan jawaban yang dipilih
        return $this->belongsTo(Opsi::class, 'jawaban', 'id_opsi');
    }
}