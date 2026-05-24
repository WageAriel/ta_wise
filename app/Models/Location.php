<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $table = 'location';
    protected $primaryKey = 'id_location';

    protected $fillable = [
        'kode_location',
        'kapasitas',
        'id_layout',
    ];

    public function layout()
    {
        return $this->belongsTo(Layout::class, 'id_layout', 'id_layout');
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'id_location', 'id_location');
    }
}
