<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('returns', function (Blueprint $table) {
            // id_return sebagai Primary Key & Auto Increment
            $table->id('id_return'); 
            
            $table->date('tanggal');
            $table->integer('qty');
            $table->string('kondisi'); 
            $table->text('alasan');
            $table->string('status')->default('Pending'); // Default status
            
            // Foreign Key ke tabel barang (sesuaikan dengan nama PK di tabel barang Anda)
            $table->foreignId('id_barang')
                  ->constrained('barang', 'id_barang')
                  ->onDelete('cascade');
            
            // id_inbound (bisa berupa string kode atau foreignId jika ada tabel inbounds)
            $table->string('id_inbound'); 
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('returns');
    }
};