<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutboundItem extends Model
{
    use HasFactory;

    protected $table = 'outbound_items';
    protected $primaryKey = 'id_outbound_item';

    protected $fillable = [
        'id_outbound',
        'id_inventory',
        'id_barang',
        'id_location',
        'qty',
    ];

    public function outbound()
    {
        return $this->belongsTo(Outbound::class, 'id_outbound', 'id_outbound');
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'id_inventory', 'id_inventory');
    }

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'id_location', 'id_location');
    }
}
