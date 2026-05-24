<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pertanyaan extends Model
{
    use HasFactory;

    protected $table = 'pertanyaan';
    protected $primaryKey = 'id_pertanyaan';

    protected $fillable = [
        'jenis_soal',
        'teks_pertanyaan',
        'bobot',
        'status',
    ];

    /**
     * Relasi ke opsi jawaban
     */
    public function opsis()
    {
        return $this->hasMany(Opsi::class, 'id_pertanyaan', 'id_pertanyaan');
    }

    /**
     * Relasi ke detail_soal
     */
    public function detailSoals()
    {
        return $this->hasMany(DetailSoal::class, 'id_pertanyaan', 'id_pertanyaan');
    }

    /**
     * Relasi many-to-many ke header_soal melalui detail_soal
     */
    public function headerSoals()
    {
        return $this->belongsToMany(
            HeaderSoal::class,
            'detail_soal',
            'id_pertanyaan',
            'id_soal',
            'id_pertanyaan',
            'id_soal'
        )->withTimestamps();
    }
}
