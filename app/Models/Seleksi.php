<?php

namespace App\Models;

use App\Models\JawabanSeleksi;
use Illuminate\Database\Eloquent\Model;

class Seleksi extends Model
{
    protected $table = 'seleksi';
    protected $primaryKey = 'id_seleksi';
    public $timestamps = false;

    protected $fillable = [
        'status_seleksi',
        'tanggal',
        'total_nilai',
        'id_user',
        'id_supplier',
        'id_soal',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    public function jawaban()
    {
        return $this->hasMany(JawabanSeleksi::class, 'id_seleksi');
    }
}