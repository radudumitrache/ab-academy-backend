<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_teacher')->nullable();
            $table->string('test_title');
            $table->text('test_description')->nullable();
            $table->date('due_date');
            $table->json('people_assigned')->nullable();
            $table->json('groups_assigned')->nullable();
            $table->timestamp('date_created')->useCurrent();
            $table->timestamps();

            $table->foreign('test_teacher')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
