<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupplierDocument extends Model
{
    protected $fillable = ['supplier_id', 'jenis_dokumen', 'has_document', 'file_path', 'file_name'];
}

