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
}