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
        Schema::create('vend_businesses', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("reg_no");
            $table->string("contact_name");
            $table->string("email");
            $table->string("phone");
            $table->string("state");
            $table->string("lga");
            $table->string("office_address")->nullable();
            $table->string("website")->nullable();
            $table->string("id_type")->nullable();
            $table->string("id_value")->nullable();
            $table->string("tin")->nullable();
            $table->string("vat_number")->nullable();
            $table->string("logo")->nullable();
            $table->string("social")->nullable();
            $table->boolean("verify")->default(0);
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vend_businesses');
    }
};
