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
        Schema::create('log_messages', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address')->nullable();
            $table->string('device_info')->nullable();
            $table->string('message')->nullable();
            $table->enum('type',['login','security'])->nullable();
            $table->enum('role',['user','admin'])->nullable();
            $table->string('email')->nullable();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_messages');
    }
};
