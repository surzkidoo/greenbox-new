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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 15)->nullable();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('address');
            $table->string('street_address')->nullable();
            $table->string('city');
            $table->string('lga');
            $table->string('state');
            $table->string('zip_code', 10)->nullable();
            $table->string('country');
            $table->foreignId('user_id')->constrained();
            $table->timestamps();

            // Optional: Indexes for faster querying
            $table->index(['city', 'state']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
