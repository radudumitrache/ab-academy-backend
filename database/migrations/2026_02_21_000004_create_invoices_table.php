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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('series');
            $table->string('number');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->decimal('value', 10, 2);
            $table->enum('currency', ['EUR', 'RON']);
            $table->date('due_date');
            $table->enum('status', ['draft', 'issued', 'paid', 'overdue', 'cancelled'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
            
            // Create a unique constraint for series and number combination
            $table->unique(['series', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
