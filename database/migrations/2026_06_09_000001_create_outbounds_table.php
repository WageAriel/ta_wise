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

            // Penerima — referensi ke master data
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('nama_penerima');           // snapshot saat outbound dibuat
            $table->string('alamat_tujuan');
            $table->string('kota_tujuan')->nullable();
            $table->string('telepon_penerima')->nullable();
            $table->text('keterangan_tujuan')->nullable();

            // Informasi Pengiriman - revamped
            $table->string('delivery_type')->nullable();      // 'self' | 'courier'
            $table->string('nama_driver')->nullable();         // self: nama sopir
            $table->string('plat_nomor')->nullable();          // self: plat nomor
            $table->string('phone_number')->nullable();        // self: nomor telepon sopir
            $table->string('courier_provider')->nullable();    // courier: penyedia jasa kurir
            $table->string('no_resi')->nullable();             // courier: nomor resi
            $table->date('tanggal_keluar');
            $table->text('catatan_pengiriman')->nullable();

            // Lampiran Dokumen
            $table->string('supplementary_doc_path')->nullable(); // dokumen pelengkap (URL)

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        // Master data penerima barang
        Schema::create('outbound_recipients', function (Blueprint $table) {
            $table->bigIncrements('id_recipient');
            $table->string('nama_penerima');
            $table->string('alamat_tujuan');
            $table->string('kota_tujuan')->nullable();
            $table->string('telepon_penerima')->nullable();
            $table->text('keterangan_tujuan')->nullable();
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
        Schema::dropIfExists('outbound_recipients');
    }
};
