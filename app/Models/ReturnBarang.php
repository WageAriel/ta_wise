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
        'status',
        'id_inbound',
    ];

    // Relasi ke ReturnDetail
    public function details()
    {
        return $this->hasMany(ReturnDetail::class, 'id_return', 'id_return');
    }
}