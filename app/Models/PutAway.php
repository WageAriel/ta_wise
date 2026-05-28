<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PutAway extends Model
{
    use HasFactory;

    protected $table = 'put_aways';
    protected $primaryKey = 'id_put_away';

    protected $fillable = [
        'id_inbound',
        'id_inventory',
        'qty',
    ];

    /**
     * Relasi ke tabel Inventory.
     */
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'id_inventory', 'id_inventory');
    }
}