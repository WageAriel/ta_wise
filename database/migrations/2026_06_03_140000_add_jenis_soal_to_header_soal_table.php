<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambah kolom jenis_soal ke tabel header_soal agar bisa
     * membedakan paket soal antara 'seleksi' dan 'klasifikasi'.
     */
    public function up(): void
    {
        Schema::table('header_soal', function (Blueprint $table) {
            $table->enum('jenis_soal', ['seleksi', 'klasifikasi'])
                ->default('klasifikasi')
                ->after('nama_soal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('header_soal', function (Blueprint $table) {
            $table->dropColumn('jenis_soal');
        });
    }
};
