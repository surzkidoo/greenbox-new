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
        Schema::create('vend_banks', function (Blueprint $table) {
            $table->id();
            $table->string("bank_name");
            $table->string("bank_account");
            $table->string("payment_method");
            $table->double("commission");
            $table->string("swift_code");
            $table->string("iban")->nullable();
            $table->string("bank_doc")->nullable();
            $table->string("tin_doc")->nullable();
            $table->string("pricing")->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vend_banks');
    }
};
