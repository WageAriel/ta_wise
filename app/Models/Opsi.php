<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Opsi extends Model
{
    use HasFactory;

    protected $table = 'opsi';
    protected $primaryKey = 'id_opsi';

    protected $fillable = [
        'nilai',
        'teks_opsi',
        'id_pertanyaan',
    ];

    /**
     * Relasi ke pertanyaan
     */
    public function pertanyaan()
    {
        return $this->belongsTo(Pertanyaan::class, 'id_pertanyaan', 'id_pertanyaan');
    }
}
