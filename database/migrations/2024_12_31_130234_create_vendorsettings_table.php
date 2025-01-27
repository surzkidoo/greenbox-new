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
        Schema::create('vendorsettings', function (Blueprint $table) {
            $table->id();
            $table->string('prefer_Currency')->default('Nigerian Naira');
            $table->string('prefer_language')->default('English');
            $table->string('primary_country')->default('Nigeria');
            $table->string('time_zone')->nullable();
            $table->boolean('email_notification')->default(true);
            $table->boolean('ride_notification')->default(true);
            $table->boolean('reminders')->default(true);
            $table->boolean('promotion_notification')->default(true);
            $table->boolean('Bell_notification')->default(true);
            $table->boolean('popup_notification')->default(true);
            $table->boolean('browser_notification')->default(true);
            $table->foreignId('user_id')->nullable()->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendorsettings');
    }
};
