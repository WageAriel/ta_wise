<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnBarang extends Model
{
    use HasFactory;

    // Nama tabel harus sesuai dengan migration (returns)
    protected $table = 'returns';
    
    // Primary key kustom
    protected $primaryKey = 'id_return';

    protected $fillable = [
        'tanggal',
        'qty',
        'kondisi',
        'alasan',
        'status',
        'id_barang',
        'id_inbound',
    ];

    // Relasi ke Model Barang
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }
}