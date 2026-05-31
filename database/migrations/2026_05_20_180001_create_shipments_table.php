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
        Schema::create('shipments', function (Blueprint $table) {
            $table->bigIncrements('id_shipment');
            $table->unsignedBigInteger('purchase_order_id');
            $table->string('carrier')->nullable();
            $table->string('tracking_number')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('status')->default('pending'); // pending, shipped, delivered, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('purchase_order_id')
                ->references('id')->on('purchase_orders')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
