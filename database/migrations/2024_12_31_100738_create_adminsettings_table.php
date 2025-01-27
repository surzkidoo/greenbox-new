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
        Schema::create('adminsettings', function (Blueprint $table) {
            $table->id();
            $table->string('admin_money')->nullable();
            $table->string('insurance_money')->nullable();
            $table->string('prefer_Currency')->default('Nigerian Naira');
            $table->string('prefer_language')->default('English');
            $table->string('primary_country')->default('Nigeria');
            $table->string('time_zone')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adminsettings');
    }
};
