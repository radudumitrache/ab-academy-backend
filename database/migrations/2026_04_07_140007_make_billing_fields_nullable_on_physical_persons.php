<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_profile_physical_persons', function (Blueprint $table) {
            $table->string('billing_address')->nullable()->change();
            $table->string('billing_city')->nullable()->change();
            $table->string('billing_country')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('payment_profile_physical_persons', function (Blueprint $table) {
            $table->string('billing_address')->nullable(false)->change();
            $table->string('billing_city')->nullable(false)->change();
            $table->string('billing_country')->default('Romania')->change();
        });
    }
};
