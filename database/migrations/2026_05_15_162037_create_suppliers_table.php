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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Data Perusahaan
            $table->string('nama_perusahaan');
            $table->string('no_telp_perusahaan', 20);
            $table->text('alamat_perusahaan');
            $table->string('email_perusahaan');

            // Data PIC & Bank
            $table->string('nama_pic');
            $table->string('no_telp_pic', 20);
            $table->string('email_pic');
            $table->string('nama_bank', 100);
            $table->string('no_rekening', 50);
            $table->string('atas_nama');

            // LOGIKA TAHUNAN: Periode dan Status
            $table->year('tahun_periode')->nullable(); 
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('draft');
            
            $table->text('catatan_admin')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
