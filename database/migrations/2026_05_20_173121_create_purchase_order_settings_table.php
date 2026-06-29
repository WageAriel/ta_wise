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
        Schema::create('purchase_order_settings', function (Blueprint $table) {
            $table->id();
            $table->text('description')->nullable();
            $table->text('supplier_description')->nullable();
            $table->text('admin_description')->nullable();
            $table->json('uom_options')->nullable();
            $table->integer('limit_class_a')->default(1000);
            $table->integer('limit_class_b')->default(500);
            $table->integer('limit_class_c')->default(100);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_settings');
    }
};
