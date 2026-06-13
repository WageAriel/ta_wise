<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutboundDetail extends Model
{
    use HasFactory;

    protected $table = 'outbound_details';
    protected $primaryKey = 'id_detail';

    protected $fillable = [
        'qty',
        'id_barang',
        'id_outbound'
    ];

    public function outbound()
    {
        return $this->belongsTo(Outbound::class, 'id_outbound', 'id_outbound');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }
}
