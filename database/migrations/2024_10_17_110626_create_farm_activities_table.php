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
        Schema::create('farm_activities', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("period")->nullable();
            $table->string("vendor")->nullable();
            $table->string("detail")->nullable();
            $table->string("step")->nullable();
            $table->boolean("active")->default(false);
            $table->foreignId('farm_type_id')->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('farm_activities');
    }
};
