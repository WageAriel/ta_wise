<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $table = 'inventory';
    protected $primaryKey = 'id_inventory';

    protected $fillable = [
        'qty',
        'id_barang',
        'id_subtype',
        'id_location',
    ];

    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'id_location', 'id_location');
    }

    public function subtype()
    {
        return $this->belongsTo(POItemSubtype::class, 'id_subtype', 'id_subtype');
    }
}
