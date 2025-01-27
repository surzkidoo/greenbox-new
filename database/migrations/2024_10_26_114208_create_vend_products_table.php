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
        Schema::create('vend_products', function (Blueprint $table) {
            $table->id();
            $table->string("categories");
            $table->boolean("description")->nullable();
            $table->enum("shipping",['independently','hib_logistic']);
            $table->boolean("return_policy");
            $table->foreignId('user_id')->constrained();
            $table->string("shipping_zone")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vend_products');
    }
};
