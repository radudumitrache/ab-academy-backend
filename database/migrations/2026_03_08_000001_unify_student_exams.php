<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the personal exams table that was added previously
        Schema::dropIfExists('student_personal_exams');

        // Add exam_type to exams so all exams can carry this info
        if (!Schema::hasColumn('exams', 'exam_type')) {
            Schema::table('exams', function (Blueprint $table) {
                $table->string('exam_type')->nullable()->after('name');
            });
        }

        // Add student-editable fields to the pivot table
        Schema::table('student_exam', function (Blueprint $table) {
            if (!Schema::hasColumn('student_exam', 'student_score')) {
                $table->string('student_score', 50)->nullable()->after('score');
            }
            if (!Schema::hasColumn('student_exam', 'notes')) {
                $table->text('notes')->nullable()->after('feedback');
            }
        });
    }

    public function down(): void
    {
        Schema::table('student_exam', function (Blueprint $table) {
            $table->dropColumn(['student_score', 'notes']);
        });

        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('exam_type');
        });
    }
};
