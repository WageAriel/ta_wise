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
            $table->string('status')->default('Pending'); // Default status
            
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