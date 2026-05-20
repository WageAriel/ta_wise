<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Klasifikasi extends Model
{
    use HasFactory;

    protected $table = 'klasifikasi';
    protected $primaryKey = 'id_klasifikasi';

    protected $fillable = [
        'tanggal',
        'status_klasifikasi',
        'total_nilai',
        'id_user',
        'id_supplier',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    /**
     * Relasi ke user (supplier yang mengajukan)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    /**
     * Relasi ke supplier
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier', 'id');
    }

    /**
     * Relasi ke jawaban klasifikasi
     */
    public function jawabanKlasifikasis()
    {
        return $this->hasMany(JawabanKlasifikasi::class, 'id_klasifikasi', 'id_klasifikasi');
    }

    /**
     * Relasi ke verifikasi
     */
    public function verifikasi()
    {
        return $this->hasOne(Verifikasi::class, 'id_klasifikasi', 'id_klasifikasi');
    }
}
