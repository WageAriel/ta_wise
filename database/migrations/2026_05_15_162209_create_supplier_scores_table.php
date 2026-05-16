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
        Schema::create('supplier_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained()->onDelete('cascade');
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->year('tahun_periode'); // Penilaian berlaku untuk tahun berapa

            $table->tinyInteger('skor_kelengkapan_dokumen')->default(0);
            $table->tinyInteger('skor_nib')->default(0);
            $table->tinyInteger('skor_npwp')->default(0);
            $table->tinyInteger('skor_akta_pendirian')->default(0);
            $table->tinyInteger('skor_izin_usaha')->default(0);
            $table->tinyInteger('skor_izin_khusus')->default(0);
            $table->tinyInteger('skor_sk_domisili')->default(0);
            $table->tinyInteger('skor_laporan_keuangan')->default(0);
            
            $table->tinyInteger('total_skor')->default(0);
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_scores');
    }
};
