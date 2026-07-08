<?php

namespace App\Console\Commands;

use App\Models\CourseProduct;
use App\Models\Product;
use Illuminate\Console\Command;

class DeleteInactiveCourses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:delete-inactive-courses
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete all inactive course products (type=course, is_active=false)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $courses = Product::where('type', 'course')
            ->where('is_active', false)
            ->get();

        $count = $courses->count();

        if ($count === 0) {
            $this->info('No inactive course products found.');
            return 0;
        }

        $this->warn("Found {$count} inactive course product(s) to delete.");

        if (! $this->option('force') && ! $this->confirm("Permanently delete {$count} inactive courses?", false)) {
            $this->line('Aborted.');
            return 0;
        }

        $deleted = 0;

        foreach ($courses as $product) {
            // course_products rows are cascade-deleted via the FK constraint,
            // but forceDelete() bypasses Eloquent events — delete the child first.
            CourseProduct::where('product_id', $product->id)->delete();
            $product->forceDelete();
            $deleted++;
        }

        $this->info("Deleted {$deleted} inactive course product(s).");

        return 0;
    }
}
