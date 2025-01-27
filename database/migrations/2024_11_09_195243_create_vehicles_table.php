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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string('plate_number');
            $table->string('capacity');
            $table->string('location')->nullable();
            $table->string('delivery_type');
            $table->foreignId('user_id')->constrained();
            $table->string("model");
            $table->string("image");
            $table->foreignId('driver_id')->nullable()->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
