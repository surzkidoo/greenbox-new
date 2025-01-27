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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('img')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('weight', 10, 2);
            $table->decimal('d_price')->default(0);
            $table->unsignedInteger('view')->default(0);
            $table->unsignedInteger('stock_available')->default(0);
            $table->text('description');
            $table->boolean('active')->default(false);
            $table->boolean('available')->default(true);
            $table->enum('availability_type', ['stock', 'unlimited'])->default('stock');
            $table->foreignId('user_id')->constrained();
            $table->foreignId('product_categories_id')->constrained();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
