<?php

namespace App\Console\Commands;

use App\Models\Course;
use Illuminate\Console\Command;

class DeleteTestCourses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'courses:delete-test
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete all courses whose title contains the word "Test" (case insensitive)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $courses = Course::withTrashed()
            ->whereRaw('LOWER(title) LIKE ?', ['%test%'])
            ->get();

        $count = $courses->count();

        if ($count === 0) {
            $this->info('No courses with "Test" in the title found.');
            return 0;
        }

        $this->warn("Found {$count} course(s) with \"Test\" in the title:");
        $this->table(
            ['ID', 'Title', 'Is Active', 'Deleted At'],
            $courses->map(fn ($c) => [
                $c->id,
                $c->title,
                $c->is_active ? 'Yes' : 'No',
                $c->deleted_at ?? '—',
            ])->toArray()
        );

        if (! $this->option('force') && ! $this->confirm("Permanently delete {$count} course(s)?", false)) {
            $this->line('Aborted.');
            return 0;
        }

        $deleted = 0;

        foreach ($courses as $course) {
            $course->forceDelete();
            $deleted++;
        }

        $this->info("Permanently deleted {$deleted} course(s) with \"Test\" in the title.");

        return 0;
    }
}
