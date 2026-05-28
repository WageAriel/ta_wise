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
        Schema::create('put_aways', function (Blueprint $table) {
            $table->id('id_put_away');
            $table->string('id_inbound'); // Kode Inbound (misal: INB-001)
            $table->foreignId('id_inventory')->constrained('inventory', 'id_inventory');
            $table->integer('qty'); // JUMLAH YANG DIMASUKKAN SAAT ITU
            $table->timestamps(); // OTOMATIS MENCATAT TANGGAL & JAM
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('put_aways');
    }
};
