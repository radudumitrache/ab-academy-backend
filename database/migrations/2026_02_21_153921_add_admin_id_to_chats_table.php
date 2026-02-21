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
        Schema::table('chats', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_id')->nullable()->after('teacher_id');
            $table->foreign('admin_id')->references('id')->on('users')->onDelete('cascade');
            // Make teacher_id nullable since a chat can be with either a teacher or an admin
            $table->unsignedBigInteger('teacher_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chats', function (Blueprint $table) {
            $table->dropForeign(['admin_id']);
            $table->dropColumn('admin_id');
            $table->unsignedBigInteger('teacher_id')->nullable(false)->change();
        });
    }
};
