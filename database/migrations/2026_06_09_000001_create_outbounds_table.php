<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbounds', function (Blueprint $table) {
            $table->bigIncrements('id_outbound');
            $table->string('no_outbound')->unique(); // e.g. OB-2026-001

            // Informasi Tujuan Pengiriman
            $table->string('nama_penerima');
            $table->string('alamat_tujuan');
            $table->string('kota_tujuan')->nullable();
            $table->string('telepon_penerima')->nullable();
            $table->text('keterangan_tujuan')->nullable();

            // Informasi Pengiriman
            $table->string('nama_driver')->nullable();
            $table->string('plat_nomor')->nullable();
            $table->string('carrier')->nullable();           // ekspedisi / armada
            $table->string('no_resi')->nullable();
            $table->date('tanggal_keluar');
            $table->text('catatan_pengiriman')->nullable();

            // Lampiran Dokumen
            $table->string('nota_timbang_path')->nullable(); // weighing note
            $table->string('surat_jalan_path')->nullable();  // delivery note

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('outbound_items', function (Blueprint $table) {
            $table->bigIncrements('id_outbound_item');
            $table->unsignedBigInteger('id_outbound');
            $table->unsignedBigInteger('id_inventory'); // stok dikurangi dari sini
            $table->unsignedBigInteger('id_barang');
            $table->unsignedBigInteger('id_location');
            $table->integer('qty');
            $table->timestamps();

            $table->foreign('id_outbound')->references('id_outbound')->on('outbounds')->onDelete('cascade');
            $table->foreign('id_inventory')->references('id_inventory')->on('inventory')->onDelete('cascade');
            $table->foreign('id_barang')->references('id_barang')->on('barang')->onDelete('cascade');
            $table->foreign('id_location')->references('id_location')->on('location')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_items');
        Schema::dropIfExists('outbounds');
    }
};
