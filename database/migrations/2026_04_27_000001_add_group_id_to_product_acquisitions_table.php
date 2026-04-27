<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_acquisitions', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->nullable()->after('tests_access');
            $table->foreign('group_id')
                  ->references('group_id')->on('groups')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('product_acquisitions', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });
    }
};
