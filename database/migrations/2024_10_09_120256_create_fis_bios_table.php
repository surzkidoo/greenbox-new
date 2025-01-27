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
        Schema::create('fis_bios', function (Blueprint $table) {
            $table->id();
            $table->string('legalname');
            $table->string('email');
            $table->string('id_number');
            $table->string('id_type');
            $table->string('gender');
            $table->string('dob');
            $table->string('nationality');
            $table->string('state');
            $table->string('lga');
            $table->string('city');
            $table->string('ward');
            $table->string('address');
            $table->enum('status',['pending','rejected','activated','de-activated'])->default('pending');
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fis_bios');
    }
};
