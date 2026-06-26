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
        Schema::create('gudang', function (Blueprint $table) {
            $table->id('id_gudang');
            $table->string('nama_gudang');
            $table->timestamps();
        });

        // Insert a default Gudang
        $defaultGudangId = \Illuminate\Support\Facades\DB::table('gudang')->insertGetId([
            'nama_gudang' => 'Gudang Utama',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('layout', function (Blueprint $table) use ($defaultGudangId) {
            $table->unsignedBigInteger('id_gudang')->default($defaultGudangId)->after('id_layout');
            $table->foreign('id_gudang')->references('id_gudang')->on('gudang')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('layout', function (Blueprint $table) {
            $table->dropForeign(['id_gudang']);
            $table->dropColumn('id_gudang');
        });
        Schema::dropIfExists('gudang');
    }
};
