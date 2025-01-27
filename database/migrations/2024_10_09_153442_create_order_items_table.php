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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->integer('item_weight');
            $table->integer('item_quantity');
            $table->decimal('price', 10, 2);
            $table->decimal('delivery_fee', 10, 2)->nullable();
            $table->decimal('vendor_commision', 10, 2)->nullable();
            $table->decimal('admin_commision', 10, 2)->nullable();
            $table->decimal('insurance', 10, 2)->nullable();
            $table->decimal('sub_total', 10, 2)->nullable();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
