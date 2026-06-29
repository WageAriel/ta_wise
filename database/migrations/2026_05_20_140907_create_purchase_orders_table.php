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
        // Table untuk item types (Item A, Item B, dll) - managed by Manajer Gudang
        Schema::create('po_item_types', function (Blueprint $table) {
            $table->id('id_item_type');
            $table->string('type_name'); // "Item A", "Item B"
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Table untuk sub-types per item (A1, A2, A3, dll)
        Schema::create('po_item_subtypes', function (Blueprint $table) {
            $table->id('id_subtype');
            $table->foreignId('id_item_type')->constrained('po_item_types', 'id_item_type')->onDelete('cascade');
            $table->string('subtype_name'); // "A1", "A2", etc
            $table->text('description')->nullable();
            $table->string('uom')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Default UoM per item type configuration
        Schema::create('po_item_uom_defaults', function (Blueprint $table) {
            $table->id('id_uom_config');
            $table->foreignId('id_item_type')->constrained('po_item_types', 'id_item_type')->onDelete('cascade');
            $table->string('default_uom'); // default UoM untuk item type
            $table->boolean('force_uom')->default(true); // apakah UoM wajib pakai default
            $table->timestamps();
        });

        // Main Purchase Order table
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->string('status')->default('inquiry');
            $table->text('description')->nullable();
            $table->string('document_path')->nullable(); // Ditambahkan untuk menyimpan link dokumen dari supplier
            $table->decimal('total_price', 15, 2)->default(0);
            // Shipment fields - revamped
            $table->string('delivery_type')->nullable(); // 'self' | 'courier'
            $table->string('driver_name')->nullable();   // self: nama sopir
            $table->string('vehicle_plate')->nullable(); // self: plat nomor
            $table->string('phone_number')->nullable();  // self: nomor telepon sopir
            $table->string('courier_provider')->nullable(); // courier: penyedia jasa kurir
            $table->string('tracking_number')->nullable();  // courier: nomor resi
            $table->text('shipment_notes')->nullable();     // catatan pengiriman
            $table->string('supplementary_doc_path')->nullable(); // dokumen pelengkap (URL)
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('po_item_uom_defaults');
        Schema::dropIfExists('po_item_subtypes');
        Schema::dropIfExists('po_item_types');
    }
};
