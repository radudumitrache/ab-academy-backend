<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists('product_acquisitions');
        Schema::dropIfExists('course_products');
        Schema::dropIfExists('single_products');
        Schema::dropIfExists('products');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // single | course
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);   // always in EUR
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('single_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->boolean('teacher_assistance')->default(false);
            $table->foreignId('test_id')->nullable()->constrained('tests')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('course_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('number_of_courses');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_products');
        Schema::dropIfExists('single_products');
        Schema::dropIfExists('products');
    }
};
