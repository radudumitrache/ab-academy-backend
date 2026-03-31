<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_announcements', function (Blueprint $table) {
            $table->id('announcement_id');
            $table->string('title');
            $table->unsignedBigInteger('group_id');
            $table->text('message');
            $table->json('attached_files')->nullable();
            $table->timestamp('time_created')->useCurrent();
            $table->timestamps();

            $table->foreign('group_id')
                ->references('group_id')->on('groups')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_announcements');
    }
};
