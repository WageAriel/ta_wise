<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfilPetugas extends Model
{
    use HasFactory;

    protected $table = 'profil_petugas';

    protected $fillable = [
        'user_id',
        'nama_petugas',
        'posisi',
        'kontak',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
