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
        Schema::create('track_shippings', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['pending', 'delivered', 'in-transit','delayed','cancelled']);
            $table->timestamp('date')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('shipping_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('track_shippings');
    }
};
