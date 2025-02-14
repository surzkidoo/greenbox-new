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
        Schema::create('farms', function (Blueprint $table) {
            $table->id();
            $table->string("farm_name");
            $table->string("farm_capacity")->nullable();
            $table->string("budget")->nullable();
            $table->integer("counter")->default(0);
            $table->string("farm_size")->nullable();
            $table->string("per_plot")->nullable();
            $table->string("status")->default('pending'); //pending,rejected,deactivated,active,completed
            $table->foreignId('user_id')->constrained();
            $table->foreignId('farm_type_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farms');
    }
};
