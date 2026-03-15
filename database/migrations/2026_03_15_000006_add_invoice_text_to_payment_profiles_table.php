<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_profiles', function (Blueprint $table) {
            // Text printed on the invoice — completed by admin before the first invoice is issued
            // for profiles that have observations / special billing mentions.
            $table->text('invoice_text')->nullable()->after('observations');
            // Flag: admin must confirm the first invoice for this profile before auto-generation
            $table->boolean('invoice_confirmed')->default(false)->after('invoice_text');
        });
    }

    public function down(): void
    {
        Schema::table('payment_profiles', function (Blueprint $table) {
            $table->dropColumn(['invoice_text', 'invoice_confirmed']);
        });
    }
};
