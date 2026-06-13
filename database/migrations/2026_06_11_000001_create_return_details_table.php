<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_details', function (Blueprint $table) {
            $table->id('id_detail');
            $table->foreignId('id_return')->constrained('returns', 'id_return')->onDelete('cascade');
            $table->foreignId('id_barang')->constrained('barang', 'id_barang')->onDelete('cascade');
            $table->integer('qty');
            $table->string('kondisi');
            $table->text('alasan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_details');
    }
};
