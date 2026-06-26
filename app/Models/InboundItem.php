<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InboundItem extends Model
{
    use HasFactory;

    protected $table = 'inbound_items';
    protected $primaryKey = 'id_isi';

    protected $fillable = [
        'id_inbound',
        'id_barang',
        'id_subtype',
        'qty',
    ];

    public function inbound()
    {
        return $this->belongsTo(Inbound::class, 'id_inbound', 'id_inbound');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }

    public function subtype()
    {
        return $this->belongsTo(POItemSubtype::class, 'id_subtype', 'id_subtype');
    }
}
