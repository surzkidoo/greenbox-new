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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('phone');
            $table->string('email')->unique();
            $table->string('address')->nullable();
            $table->string('occupation')->nullable();
            $table->string('state')->nullable();
            $table->string('lga')->nullable();
            $table->string('gender')->nullable();
            $table->string('avatar')->nullable();
            $table->string('account_status')->nullable();
            $table->boolean('fis_verified')->default(false);
            $table->boolean('seller_verified')->default(false);
            $table->boolean('vendor_verified')->default(false);
            $table->boolean('email_verified')->default(false);
            $table->string('refer_by')->nullable();
            $table->string('access_type')->nullable();
            $table->string('referral_code')->nullable();
            $table->enum('role', ['vendor', 'user','admin'])->default('user');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
