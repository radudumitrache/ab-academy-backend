<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->string('gcs_path')->nullable()->after('allowed_users');
            $table->unsignedBigInteger('uploader_id')->nullable()->after('gcs_path');
            $table->string('folder')->default('private')->after('uploader_id'); // 'private' or 'common'
        });
    }

    public function down(): void
    {
        Schema::table('materials', function (Blueprint $table) {
            $table->dropColumn(['gcs_path', 'uploader_id', 'folder']);
        });
    }
};
