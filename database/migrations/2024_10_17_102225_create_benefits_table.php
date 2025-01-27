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
        Schema::create('benefits', function (Blueprint $table) {
            $table->id();
            $table->string('farm_name')->nullable();
            $table->string('farm_produce')->nullable();
            $table->string('working_cost')->nullable();
            $table->string('quantity_required')->nullable();
            $table->string('unit_price')->nullable();
            $table->string('measures')->nullable();
            $table->string('variable_cost')->nullable();
            $table->string('defect_liability')->nullable();
            $table->string('total_sales')->nullable();
            $table->string('fixed_assets')->nullable();
            $table->string('tax')->nullable();
            $table->string('gross_profit')->nullable();
            $table->string('net_profit')->nullable();
            $table->foreignId('farm_type_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('benefits');
    }
};
