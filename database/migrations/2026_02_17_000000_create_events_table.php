<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', ['class', 'meeting', 'other']);
            $table->date('event_date');
            $table->time('event_time');
            $table->unsignedInteger('event_duration');
            $table->unsignedBigInteger('event_organizer');
            $table->json('guests')->nullable();
            $table->string('event_meet_link')->nullable();
            $table->text('event_notes')->nullable();
            $table->timestamps();

            $table->foreign('event_organizer')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
