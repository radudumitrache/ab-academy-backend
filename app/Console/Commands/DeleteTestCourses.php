<?php

namespace App\Console\Commands;

use App\Models\CourseProduct;
use App\Models\Product;
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
    protected $description = 'Permanently delete all course products whose name contains the word "Test" (case insensitive)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $courses = Product::withTrashed()
            ->where('type', 'course')
            ->whereRaw('LOWER(name) LIKE ?', ['%test%'])
            ->get();

        $count = $courses->count();

        if ($count === 0) {
            $this->info('No course products with "Test" in the name found.');
            return 0;
        }

        $this->warn("Found {$count} course product(s) with \"Test\" in the name:");
        $this->table(
            ['ID', 'Name', 'Is Active', 'Deleted At'],
            $courses->map(fn ($p) => [
                $p->id,
                $p->name,
                $p->is_active ? 'Yes' : 'No',
                $p->deleted_at ?? '—',
            ])->toArray()
        );

        if (! $this->option('force') && ! $this->confirm("Permanently delete {$count} course product(s)?", false)) {
            $this->line('Aborted.');
            return 0;
        }

        $deleted = 0;

        foreach ($courses as $product) {
            // Delete the child course_products row before force-deleting the parent
            CourseProduct::where('product_id', $product->id)->delete();
            $product->forceDelete();
            $deleted++;
        }

        $this->info("Permanently deleted {$deleted} course product(s) with \"Test\" in the name.");

        return 0;
    }
}
