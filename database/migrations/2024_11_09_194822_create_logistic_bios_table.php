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
        Schema::create('logistic_bios', function (Blueprint $table) {
            $table->id();
            $table->string('business_name');
            $table->string("reg_no");
            $table->string("contact_name");
            $table->string("phone");
            $table->string("office_address")->nullable();
            $table->string("website")->nullable();
            $table->string("social")->nullable();
            $table->string("logo_image")->nullable();
            $table->string('email')->unique();
            $table->string("coverage_area");
            $table->set('service_type', ['next_day_delivery', 'same_day_delivery', 'international_delivery','cold_storage', 'specialized'])->nullable();
            $table->string("fleet_info");
            $table->string("max_weight");
            $table->string("special_handle");
            $table->string("insurance_coverage");
            $table->string("tracking_capability");
            $table->string("licenses_image")->nullable();
            $table->string("insurance_image")->nullable();
            $table->string("terms_conditions_pdf")->nullable();
            $table->string("tax_tin")->nullable();
            $table->string("pricing_structure");
            $table->string("payment_method");
            $table->string("service_level_agreement");
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('logistic_bios');
    }
};
