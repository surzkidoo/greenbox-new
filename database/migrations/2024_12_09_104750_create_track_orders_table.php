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
        Schema::create('track_orders', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['order placed', 'pending confirmation', 'on delivery', 'delivered'])->nullable();
            $table->timestamp('date')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('order_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('track_orders');
    }
};
