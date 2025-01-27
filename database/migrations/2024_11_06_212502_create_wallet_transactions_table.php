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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->decimal('old_balance',10,2)->default(0);
            $table->decimal('new_balance',10,2)->default(0);
            $table->decimal('amount',10,2)->default(0);
            $table->enum('status', ['pending', 'success', 'failed']);
            $table->enum('transaction_type', ['withdraw', 'deposit', 'refund']);
            $table->date('date');
            $table->foreignId('wallet_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
