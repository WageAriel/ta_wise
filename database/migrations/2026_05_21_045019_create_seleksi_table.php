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
        Schema::create('seleksi', function (Blueprint $table) {
            $table->id('id_seleksi');
            $table->enum('status_seleksi', ['Menunggu Validasi', 'Lolos', 'Tidak Lolos'])->default('Menunggu Validasi');
            $table->date('tanggal');
            $table->integer('total_nilai');
            $table->foreignId('id_user')->constrained('users', 'id')->onDelete('cascade');
            $table->foreignId('id_supplier')->constrained('suppliers', 'id')->onDelete('cascade');
            $table->foreignId('id_soal')->constrained('header_soal', 'id_soal')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seleksi');
    }
};
