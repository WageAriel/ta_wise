<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    use HasFactory;

    protected $table = 'barang';
    protected $primaryKey = 'id_barang';

    protected $fillable = [
        'nama_barang',
        'satuan',
        'status',
        'id_item_type',
        'min_stock',
        'max_stock',
    ];

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'id_barang', 'id_barang');
    }
}
