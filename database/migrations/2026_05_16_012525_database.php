<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabel tanpa dependensi (Master Tables)
        Schema::create('kelas', function (Blueprint $table) {
            $table->id('id_kelas');
            $table->string('nama_kelas');
            $table->timestamps();
        });

        Schema::create('header_soal', function (Blueprint $table) {
            $table->id('id_soal');
            $table->string('nama_soal', 255);
            $table->timestamps();
        });

        Schema::create('pertanyaan', function (Blueprint $table) {
            $table->id('id_pertanyaan');
            $table->enum('jenis_soal', ['pilihan_ganda', 'essay']); // Sesuaikan isi enum Anda
            $table->text('teks_pertanyaan');
            $table->integer('bobot');
            $table->string('status');
            $table->timestamps();
        });

        // 2. Tabel yang bergantung pada Master Tables
        Schema::create('supplier_kelas', function (Blueprint $table) {
            $table->id('id_supplier_kelas');
            $table->date('tanggal');
            $table->string('status');
            $table->foreignId('id_kelas')->constrained('kelas', 'id_kelas')->onDelete('cascade');
            $table->foreignId('id_supplier')->constrained('suppliers', 'id')->onDelete('cascade'); // Asumsi ada tabel suppliers
            $table->timestamps();
        });

        Schema::create('detail_soal', function (Blueprint $table) {
            $table->id('id_detail');
            $table->foreignId('id_pertanyaan')->constrained('pertanyaan', 'id_pertanyaan')->onDelete('cascade');
            $table->foreignId('id_soal')->constrained('header_soal', 'id_soal')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('opsi', function (Blueprint $table) {
            $table->id('id_opsi');
            $table->integer('nilai');
            $table->string('teks_opsi');
            $table->foreignId('id_pertanyaan')->constrained('pertanyaan', 'id_pertanyaan')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('klasifikasi', function (Blueprint $table) {
            $table->id('id_klasifikasi');
            $table->date('tanggal');
            $table->string('status_klasifikasi');
            $table->integer('total_nilai');
            $table->foreignId('id_user')->constrained('users', 'id')->onDelete('cascade'); // Asumsi ada tabel users bawaan laravel
            $table->foreignId('id_supplier')->constrained('suppliers', 'id')->onDelete('cascade');
            $table->timestamps();
        });

        // 3. Tabel Verifikasi (Butuh id_klasifikasi)
        Schema::create('verifikasi', function (Blueprint $table) {
            $table->id('id_verifikasi');
            $table->integer('total_nilai');
            $table->enum('status', ['lulus', 'tidak_lulus']); // Sesuaikan isi enum Anda
            $table->date('tanggal');
            $table->string('rekomendasi_sistem');
            $table->string('keputusan_admin');
            $table->foreignId('id_klasifikasi')->unique()->constrained('klasifikasi', 'id_klasifikasi')->onDelete('cascade');
            $table->foreignId('id_user_admin')->constrained('users', 'id')->onDelete('cascade');
            $table->foreignId('id_user_petugas')->constrained('users', 'id')->onDelete('cascade');
            $table->timestamps();
        });

        // 4. Tabel Jawaban Klasifikasi (Butuh opsi, pertanyaan, klasifikasi, & verifikasi)
        Schema::create('jawaban_klasifikasi', function (Blueprint $table) {
            $table->id('id_jawaban');
            $table->enum('jawaban_verifikasi', ['valid', 'invalid']); // Sesuaikan isi enum Anda
            $table->foreignId('id_klasifikasi')->constrained('klasifikasi', 'id_klasifikasi')->onDelete('cascade');
            $table->foreignId('id_pertanyaan')->constrained('pertanyaan', 'id_pertanyaan')->onDelete('cascade');
            $table->foreignId('id_opsi')->constrained('opsi', 'id_opsi')->onDelete('cascade');
            $table->foreignId('id_verifikasi')->constrained('verifikasi', 'id_verifikasi')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Urutan drop dibalik dari bawah ke atas agar tidak melanggar foreign key constraint
        Schema::dropIfExists('jawaban_klasifikasi');
        Schema::dropIfExists('verifikasi');
        Schema::dropIfExists('klasifikasi');
        Schema::dropIfExists('opsi');
        Schema::dropIfExists('detail_soal');
        Schema::dropIfExists('supplier_kelas');
        Schema::dropIfExists('pertanyaan');
        Schema::dropIfExists('header_soal');
        Schema::dropIfExists('kelas');
    }
};