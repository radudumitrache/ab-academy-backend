<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->upSqlite();
        } else {
            $this->upMysql();
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->downSqlite();
        } else {
            $this->downMysql();
        }
    }

    // -------------------------------------------------------------------------
    // SQLite: recreate the table because ALTER TABLE is too limited
    // -------------------------------------------------------------------------

    private function upSqlite(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::create('exams_new', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('');
            $table->date('date')->nullable();
            $table->enum('status', ['upcoming', 'to_be_corrected', 'passed', 'failed'])->default('upcoming');
            $table->timestamps();
        });

        $existing = Schema::getColumnListing('exams');
        $hasName   = in_array('name', $existing);
        $hasDate   = in_array('date', $existing);
        $hasStatus = in_array('status', $existing);

        $name   = $hasName   ? 'name'            : "''";
        $date   = $hasDate   ? 'DATE(date)'       : 'NULL';
        $status = $hasStatus ? 'status'           : "'upcoming'";

        DB::statement("INSERT INTO exams_new (id, name, date, status, created_at, updated_at)
                        SELECT id, {$name}, {$date}, {$status}, created_at, updated_at
                        FROM exams");

        Schema::drop('exams');
        DB::statement('ALTER TABLE exams_new RENAME TO exams');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    private function downSqlite(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::create('exams_new', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('');
            $table->dateTime('date')->nullable();
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->enum('status', ['upcoming', 'to_be_corrected', 'passed', 'failed'])->default('upcoming');
            $table->timestamps();
        });

        DB::statement("INSERT INTO exams_new (id, name, date, status, created_at, updated_at)
                        SELECT id, name, date, status, created_at, updated_at FROM exams");

        Schema::drop('exams');
        DB::statement('ALTER TABLE exams_new RENAME TO exams');

        DB::statement('PRAGMA foreign_keys = ON');
    }

    // -------------------------------------------------------------------------
    // MySQL: use ALTER TABLE with existence checks for each column
    // -------------------------------------------------------------------------

    private function upMysql(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (!Schema::hasColumn('exams', 'name')) {
                $table->string('name')->after('id');
            }

            if (Schema::hasColumn('exams', 'teacher_id')) {
                $table->dropForeign(['teacher_id']);
                $table->dropColumn('teacher_id');
            }

            if (Schema::hasColumn('exams', 'date')) {
                $table->date('date')->change();
            } else {
                $table->date('date')->nullable()->after('name');
            }

            if (!Schema::hasColumn('exams', 'status')) {
                $table->enum('status', ['upcoming', 'to_be_corrected', 'passed', 'failed'])
                      ->default('upcoming')
                      ->after('date');
            }
        });
    }

    private function downMysql(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'date')) {
                $table->dateTime('date')->change();
            }

            if (!Schema::hasColumn('exams', 'teacher_id')) {
                $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('cascade');
            }
        });
    }
};
