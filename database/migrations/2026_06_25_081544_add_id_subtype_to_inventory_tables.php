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
        Schema::table('inbound_items', function (Blueprint $table) {
            $table->foreignId('id_subtype')->nullable()->constrained('po_item_subtypes', 'id_subtype')->onDelete('cascade');
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->foreignId('id_subtype')->nullable()->constrained('po_item_subtypes', 'id_subtype')->onDelete('cascade');
        });

        Schema::table('return_details', function (Blueprint $table) {
            $table->foreignId('id_subtype')->nullable()->constrained('po_item_subtypes', 'id_subtype')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('return_details', function (Blueprint $table) {
            $table->dropForeign(['id_subtype']);
            $table->dropColumn('id_subtype');
        });

        Schema::table('inventory', function (Blueprint $table) {
            $table->dropForeign(['id_subtype']);
            $table->dropColumn('id_subtype');
        });

        Schema::table('inbound_items', function (Blueprint $table) {
            $table->dropForeign(['id_subtype']);
            $table->dropColumn('id_subtype');
        });
    }
};
