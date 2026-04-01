<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY COLUMN notification_type ENUM('Exam', 'Schedule', 'Homework', 'Message', 'Payment', 'Announcement') NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE notifications MODIFY COLUMN notification_type ENUM('Exam', 'Schedule', 'Homework', 'Message', 'Payment') NULL");
    }
};
