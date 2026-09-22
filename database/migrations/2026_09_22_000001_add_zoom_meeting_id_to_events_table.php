<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Records the Zoom meeting an event owns.
     *
     * Without it the platform could create meetings but never delete or reschedule
     * them: a deleted or moved event left an orphaned Zoom meeting blocking its slot,
     * and the host start_url could not be refreshed once its embedded token expired.
     *
     * Stored as a string — Zoom meeting IDs are numeric but long enough to lose
     * precision in JavaScript, and Zoom documents them as strings.
     */
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('zoom_meeting_id', 32)->nullable()->after('meeting_account_id');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('zoom_meeting_id');
        });
    }
};
