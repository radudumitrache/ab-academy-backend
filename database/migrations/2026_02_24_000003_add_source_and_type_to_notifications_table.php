<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('notifications', 'notification_source')) {
                $table->enum('notification_source', ['Admin', 'Student', 'Teacher'])
                      ->nullable()
                      ->after('is_seen');
            }

            if (!Schema::hasColumn('notifications', 'notification_type')) {
                $table->enum('notification_type', ['Exam', 'Schedule', 'Homework', 'Message', 'Payment'])
                      ->nullable()
                      ->after('notification_source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn(['notification_source', 'notification_type']);
        });
    }
};
