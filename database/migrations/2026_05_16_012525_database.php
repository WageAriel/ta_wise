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
            $table->enum('jenis_soal', ['seleksi', 'klasifikasi']); // Sesuaikan isi enum Anda
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
            $table->foreignId('id_soal')->constrained('header_soal', 'id_soal')->onDelete('cascade');
            $table->timestamps();
        });

        // 3. Tabel Verifikasi (Butuh id_klasifikasi)
        Schema::create('verifikasi', function (Blueprint $table) {
            $table->id('id_verifikasi');
            $table->integer('total_nilai');
            $table->string('status')->default('menunggu_admin');
            $table->date('tanggal');
            $table->string('rekomendasi_sistem');
            $table->string('keputusan_admin')->nullable();
            $table->foreignId('id_klasifikasi')->unique()->constrained('klasifikasi', 'id_klasifikasi')->onDelete('cascade');
            $table->foreignId('id_user_admin')->nullable()->constrained('users', 'id')->onDelete('cascade');
            $table->foreignId('id_user_petugas')->constrained('users', 'id')->onDelete('cascade');
            $table->timestamps();
        });

        // 4. Tabel Jawaban Klasifikasi (Butuh opsi, pertanyaan, klasifikasi, & verifikasi)
        Schema::create('jawaban_klasifikasi', function (Blueprint $table) {
            $table->id('id_jawaban');
            $table->string('jawaban_verifikasi', 20)->nullable();
            $table->foreignId('id_klasifikasi')->constrained('klasifikasi', 'id_klasifikasi')->onDelete('cascade');
            $table->foreignId('id_pertanyaan')->constrained('pertanyaan', 'id_pertanyaan')->onDelete('cascade');
            $table->foreignId('id_opsi')->constrained('opsi', 'id_opsi')->onDelete('cascade');
            $table->unsignedBigInteger('id_opsi_verifikasi')->nullable();
            $table->foreign('id_opsi_verifikasi')->references('id_opsi')->on('opsi')->onDelete('set null');
            $table->text('catatan_verifikasi')->nullable();
            $table->foreignId('id_verifikasi')->nullable()->constrained('verifikasi', 'id_verifikasi')->onDelete('cascade');
            $table->timestamps();
        });

        // 5. Tabel Profil Petugas dan Jadwal Kunjungan
        Schema::create('profil_petugas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade');
            $table->string('nama_petugas')->nullable();
            $table->string('posisi')->nullable();
            $table->string('kontak')->nullable();
            $table->timestamps();
        });

        Schema::create('jadwal_kunjungan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_klasifikasi')->constrained('klasifikasi', 'id_klasifikasi')->onDelete('cascade');
            $table->foreignId('id_user_petugas')->constrained('users', 'id')->onDelete('cascade');
            $table->date('tanggal_kunjungan');
            $table->time('waktu_kunjungan');
            $table->enum('status', ['menunggu', 'berlangsung', 'selesai', 'dibatalkan'])->default('menunggu');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Urutan drop dibalik dari bawah ke atas agar tidak melanggar foreign key constraint
        Schema::dropIfExists('jadwal_kunjungan');
        Schema::dropIfExists('profil_petugas');
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