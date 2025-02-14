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
        Schema::create('fis_farms', function (Blueprint $table) {
            $table->id();
            $table->string('farm_name');
            $table->string('prod_type');
            $table->string('ownership');
            $table->string('farm_geo');
            $table->string('soil_type');
            $table->string('soil_test');
            $table->string('farm_size');
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fis_farms');
    }
};
