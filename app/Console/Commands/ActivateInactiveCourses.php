<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class ActivateInactiveCourses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'courses:activate-inactive
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set all inactive course products (type=course, is_active=false) to active';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = Product::where('type', 'course')
            ->where('is_active', false)
            ->count();

        if ($count === 0) {
            $this->info('No inactive course products found.');
            return 0;
        }

        $this->warn("Found {$count} inactive course product(s).");

        if (! $this->option('force') && ! $this->confirm("Set {$count} course product(s) to active?", false)) {
            $this->line('Aborted.');
            return 0;
        }

        $updated = Product::where('type', 'course')
            ->where('is_active', false)
            ->update(['is_active' => true]);

        $this->info("Activated {$updated} course product(s).");

        return 0;
    }
}
