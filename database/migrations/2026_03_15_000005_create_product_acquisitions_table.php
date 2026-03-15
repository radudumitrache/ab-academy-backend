<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_acquisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_profile_id')->constrained('payment_profiles');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('student_id')->constrained('users');

            // Amount paid and currency at time of purchase
            $table->decimal('amount_paid', 10, 2);
            $table->enum('currency', ['EUR', 'RON']);

            // Payment flow
            $table->string('order_key', 20)->unique()->nullable(); // EuPlatesc order key
            $table->enum('acquisition_status', ['pending_payment', 'paid', 'active', 'completed', 'cancelled', 'expired'])->default('pending_payment');
            $table->text('acquisition_notes')->nullable();

            // Access granted by admin (JSON arrays of IDs)
            $table->json('groups_access')->nullable();   // group IDs for course products
            $table->json('tests_access')->nullable();    // test IDs for single products

            // Lifecycle dates
            $table->date('acquisition_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->boolean('is_completed')->default(false);

            // Invoice info (replaces old Invoice model for product purchases)
            $table->string('invoice_series')->nullable();
            $table->string('invoice_number')->nullable();

            // EuPlatesc payment tracking
            $table->string('ep_id')->nullable();
            $table->string('payment_status_message')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Renewal chain
            $table->foreignId('renewed_from_id')->nullable()->constrained('product_acquisitions')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_acquisitions');
    }
};
