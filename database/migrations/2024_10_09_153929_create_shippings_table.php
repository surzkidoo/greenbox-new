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
        Schema::create('shippings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->onDelete('cascade');
            $table->string('tracking_number')->nullable();
            $table->date('shipped_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->foreignId('logistic_id')->nullable()->constrained('users');
            $table->enum('status', ['pending', 'delivered', 'in-transit','delayed','cancelled']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shippings');
    }
};
