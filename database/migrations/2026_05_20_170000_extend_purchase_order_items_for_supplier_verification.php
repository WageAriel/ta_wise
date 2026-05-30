<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            // Supplier's offered price and quantity for negotiation
            $table->decimal('supplier_offered_price', 15, 2)->nullable()->after('uom');
            $table->integer('supplier_offered_quantity')->nullable()->after('supplier_offered_price');

            // Counter offer from admin to supplier
            $table->decimal('counter_offered_price', 15, 2)->nullable()->after('supplier_offered_quantity');
            $table->integer('counter_offered_quantity')->nullable()->after('counter_offered_price');

            // Final accepted price/quantity
            $table->decimal('final_price', 15, 2)->nullable()->after('counter_offered_quantity');
            $table->integer('final_quantity')->nullable()->after('final_price');

            // Document verification tracking
            $table->boolean('doc_verified')->default(false)->after('final_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn([
                'supplier_offered_price',
                'supplier_offered_quantity',
                'counter_offered_price',
                'counter_offered_quantity',
                'final_price',
                'final_quantity',
                'doc_verified',
            ]);
        });
    }
};
