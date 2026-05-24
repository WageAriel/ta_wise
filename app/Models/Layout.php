<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Layout extends Model
{
    use HasFactory;

    protected $table = 'layout';
    protected $primaryKey = 'id_layout';

    protected $fillable = [
        'nama_layout',
    ];

    public function locations()
    {
        return $this->hasMany(Location::class, 'id_layout', 'id_layout');
    }
}
