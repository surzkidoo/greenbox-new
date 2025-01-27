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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('2fa')->default(false);
            $table->boolean('live_location')->default(false);
            $table->boolean('team_link')->default(false);
            $table->boolean('weather')->default(false);
            $table->boolean('humidity')->default(false);
            $table->string('default_shipping')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Foreign key constraint
            $table->timestamps(); // Created_at and Updated_at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
