<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            // SmartBill invoice number returned after successful creation (e.g. "INV-000001")
            $table->string('smartbill_number')->nullable()->after('status');
            // Whether the invoice has been pushed to SmartBill
            $table->boolean('smartbill_synced')->default(false)->after('smartbill_number');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['smartbill_number', 'smartbill_synced']);
        });
    }
};
