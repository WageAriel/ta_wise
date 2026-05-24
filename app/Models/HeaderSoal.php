<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeaderSoal extends Model
{
    use HasFactory;

    protected $table = 'header_soal';
    protected $primaryKey = 'id_soal';

    protected $fillable = [
        'nama_soal',
    ];

    /**
     * Relasi ke tabel detail_soal (pivot antara header dan pertanyaan)
     */
    public function detailSoal()
    {
        return $this->hasMany(DetailSoal::class, 'id_soal', 'id_soal');
    }

    /**
     * Relasi many-to-many ke pertanyaan melalui detail_soal
     */
    public function pertanyaans()
    {
        return $this->belongsToMany(
            Pertanyaan::class,
            'detail_soal',
            'id_soal',
            'id_pertanyaan',
            'id_soal',
            'id_pertanyaan'
        )->withTimestamps();
    }
}
