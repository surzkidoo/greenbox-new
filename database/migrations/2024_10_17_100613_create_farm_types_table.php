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
        Schema::create('farm_types', function (Blueprint $table) {
            $table->id();
            $table->string('farm_name')->nullable();
            $table->string('farm_url')->nullable();
            $table->string('farm_produce')->nullable();
            $table->enum('farm_type', ['crop', 'livestock']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farm_types');
    }
};
