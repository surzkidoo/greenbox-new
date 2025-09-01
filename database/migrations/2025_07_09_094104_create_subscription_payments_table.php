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
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_user_id')->constrained('subscription_users')->onDelete('cascade');
            $table->decimal('amount', 10, 2)->default(0.00);
            $table->string('payment_method')->nullable(); // e.g., 'credit_card', 'paypal'
            $table->string('transaction_id')->nullable(); // Transaction ID for payment tracking
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending'); // Payment status
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
