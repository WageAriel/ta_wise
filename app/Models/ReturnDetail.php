<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnDetail extends Model
{
    use HasFactory;

    protected $table = 'return_details';
    protected $primaryKey = 'id_detail';

    protected $fillable = [
        'id_return',
        'id_barang',
        'qty',
        'kondisi',
        'alasan',
    ];

    public function returnTransaction()
    {
        return $this->belongsTo(ReturnBarang::class, 'id_return', 'id_return');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }
}
