<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_profile_companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_profile_id')->constrained('payment_profiles')->cascadeOnDelete();
            $table->string('cui');                          // Romanian tax ID
            $table->string('company_name');
            $table->string('trade_register_number');
            $table->date('registration_date')->nullable();
            $table->string('legal_address');
            $table->string('billing_address');
            $table->string('billing_city');
            $table->string('billing_state')->nullable();
            $table->string('billing_zip_code')->nullable();
            $table->string('billing_country')->default('Romania');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_profile_companies');
    }
};
