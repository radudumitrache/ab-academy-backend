<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();

            // The 7-character order key sent to EuPlatesc as orderId
            $table->string('order_key', 20)->unique();

            // Amount and currency as sent to EuPlatesc
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10);

            // EuPlatesc response fields
            $table->integer('status_code')->nullable();      // 0 = approved, non-zero = error
            $table->string('status_message')->nullable();    // e.g. "Approved"
            $table->string('ep_id')->nullable();             // EuPlatesc internal transaction ID
            $table->timestamp('paid_at')->nullable();

            // Status: pending | approved | failed
            $table->string('status', 20)->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
    }
};
