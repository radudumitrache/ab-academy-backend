<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_profile_physical_persons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_profile_id')->constrained('payment_profiles')->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('billing_address')->nullable();
            $table->string('billing_city');
            $table->string('billing_state')->nullable();
            $table->string('billing_zip_code')->nullable();
            $table->string('billing_country')->default('Romania');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_profile_physical_persons');
    }
};
