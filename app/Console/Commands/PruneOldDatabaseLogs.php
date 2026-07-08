<?php

namespace App\Console\Commands;

use App\Models\DatabaseLog;
use Illuminate\Console\Command;

class PruneOldDatabaseLogs extends Command
{
    protected $signature = 'logs:prune
                            {--force : Skip confirmation prompt}';

    protected $description = 'Delete DatabaseLog entries older than 1 week';

    public function handle(): int
    {
        $cutoff = now()->subWeek();

        $count = DatabaseLog::where('created_at', '<', $cutoff)->count();

        if ($count === 0) {
            $this->info('No database logs older than 1 week found.');
            return 0;
        }

        $this->warn("Found {$count} log(s) older than 1 week.");

        if (! $this->option('force') && ! $this->confirm("Permanently delete {$count} log(s)?", false)) {
            $this->line('Aborted.');
            return 0;
        }

        DatabaseLog::where('created_at', '<', $cutoff)->delete();

        $this->info("Deleted {$count} log(s).");

        return 0;
    }
}
