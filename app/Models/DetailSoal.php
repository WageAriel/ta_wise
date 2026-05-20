<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailSoal extends Model
{
    use HasFactory;

    protected $table = 'detail_soal';
    protected $primaryKey = 'id_detail';

    protected $fillable = [
        'id_pertanyaan',
        'id_soal',
    ];

    /**
     * Relasi ke header soal
     */
    public function headerSoal()
    {
        return $this->belongsTo(HeaderSoal::class, 'id_soal', 'id_soal');
    }

    /**
     * Relasi ke pertanyaan
     */
    public function pertanyaan()
    {
        return $this->belongsTo(Pertanyaan::class, 'id_pertanyaan', 'id_pertanyaan');
    }
}
