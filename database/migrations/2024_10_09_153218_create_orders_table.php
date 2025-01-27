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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->decimal('total', 10, 2)->nullable();
            $table->decimal('sub_total', 10, 2)->nullable();
            $table->decimal('total_shipping_fee', 10, 2)->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->enum('shipping_method', ['landmark', 'personal'])->nullable();
            $table->enum('type', ['express', 'standard'])->default('standard');
            $table->foreignId('landmark_id')->nullable()->constrained();
            $table->text('note')->nullable();
            $table->enum('status', ['in-complete','pending', 'completed', 'on_delivery', 'delivered', 'cancelled','refund','refunded','ended'])->default('pending');
            $table->foreignId('coupon_id')->nullable()->constrained();
            $table->foreignId('billing_address_id')->nullable()->constrained('addresses');
            $table->foreignId('shipping_address_id')->nullable()->constrained('addresses');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
