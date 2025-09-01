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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('plan_name')->nullable(); // Name of the subscription plan
            $table->decimal('price', 10, 2)->default(0.00); // Price of the subscription plan
            $table->text('description')->nullable(); // Description of the subscription plan
            $table->boolean('is_active')->default(true); // Whether the plan is active or not
            $table->string('billing_cycle')->default('monthly'); // Duration in days
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
