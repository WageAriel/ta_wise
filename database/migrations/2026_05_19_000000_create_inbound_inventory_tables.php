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
        Schema::create('barang', function (Blueprint $table) {
            $table->id('id_barang');
            $table->string('nama_barang');
            $table->string('satuan');
            $table->string('status');
            $table->unsignedBigInteger('id_item_type')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('purchase_order_items')) {
            Schema::table('purchase_order_items', function (Blueprint $table) {
                $table->foreign('barang_id')
                    ->references('id_barang')
                    ->on('barang')
                    ->onDelete('cascade');
            });
        }

        Schema::create('layout', function (Blueprint $table) {
            $table->id('id_layout');
            $table->string('nama_layout');
            $table->timestamps();
        });

        Schema::create('location', function (Blueprint $table) {
            $table->id('id_location');
            $table->string('kode_location');
            $table->integer('kapasitas');
            $table->foreignId('id_layout')->constrained('layout', 'id_layout')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('inventory', function (Blueprint $table) {
            $table->id('id_inventory');
            $table->integer('qty');
            $table->foreignId('id_barang')->constrained('barang', 'id_barang')->onDelete('cascade');
            $table->foreignId('id_location')->constrained('location', 'id_location')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('purchase_order_items')) {
            try {
                Schema::table('purchase_order_items', function (Blueprint $table) {
                    $table->dropForeign(['barang_id']);
                });
            } catch (\Throwable $e) {
                // Foreign key may already be absent in databases that were partially migrated.
            }
        }

        Schema::dropIfExists('inventory');
        Schema::dropIfExists('location');
        Schema::dropIfExists('layout');
        Schema::dropIfExists('barang');
    }
};
