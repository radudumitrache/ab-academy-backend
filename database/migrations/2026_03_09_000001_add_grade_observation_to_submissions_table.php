<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homework_submissions', function (Blueprint $table) {
            $table->string('grade')->nullable()->after('submitted_at');
            $table->text('observation')->nullable()->after('grade');
        });

        Schema::table('test_submissions', function (Blueprint $table) {
            $table->string('grade')->nullable()->after('submitted_at');
            $table->text('observation')->nullable()->after('grade');
        });
    }

    public function down(): void
    {
        Schema::table('homework_submissions', function (Blueprint $table) {
            $table->dropColumn(['grade', 'observation']);
        });

        Schema::table('test_submissions', function (Blueprint $table) {
            $table->dropColumn(['grade', 'observation']);
        });
    }
};
