<?php

namespace App\Console\Commands;

use App\Models\CourseProduct;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportCoursesFromJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:import-courses
                            {--file= : Path to the JSON file (default: ../courses.json relative to Laravel root)}
                            {--force : Update existing products instead of skipping them}
                            {--dry-run : Preview what would be imported without writing to the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import courses from a JSON export file into the products and course_products tables';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $filePath = $this->option('file') ?: base_path('../courses.json');
        $isDryRun = $this->option('dry-run');
        $isForce  = $this->option('force');

        // ── 1. Resolve file ──────────────────────────────────────────────────
        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        // ── 2. Decode JSON ───────────────────────────────────────────────────
        $raw = file_get_contents($filePath);
        $items = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($items)) {
            $this->error('Failed to parse JSON: ' . json_last_error_msg());
            return 1;
        }

        $total = count($items);
        $this->info("Found {$total} courses in {$filePath}");

        if ($isDryRun) {
            $this->warn('--dry-run mode: no database writes will occur.');
        }

        // ── 3. Confirm ───────────────────────────────────────────────────────
        if (! $isDryRun && ! $this->confirm("Import {$total} courses into the database?", true)) {
            $this->line('Aborted.');
            return 0;
        }

        // ── 4. Import ────────────────────────────────────────────────────────
        $created  = 0;
        $updated  = 0;
        $skipped  = 0;
        $errors   = [];

        $this->output->newLine();

        $this->withProgressBar($items, function (array $item, $bar) use (
            $isDryRun, $isForce, &$created, &$updated, &$skipped, &$errors
        ) {
            static $index = 0;
            $index++;

            try {
                // ── a. Extract translation ───────────────────────────────────
                $translations = $item['translations'] ?? [];
                $translation  = collect($translations)->firstWhere('locale', 'en')
                             ?? ($translations[0] ?? null);

                if (! $translation) {
                    $errors[] = ["#{$index}", $item['id'] ?? '?', 'No translation found'];
                    return;
                }

                $name             = trim($translation['title'] ?? '');
                $description      = $translation['description'] ?? null;
                $price            = isset($item['price']) && $item['price'] !== null
                                        ? (float) $item['price']
                                        : 0.00;
                $numberOfCourses  = isset($item['number_courses']) && $item['number_courses'] !== null
                                        ? (int) $item['number_courses']
                                        : 1;

                if (empty($name)) {
                    $errors[] = ["#{$index}", $item['id'] ?? '?', 'Empty title'];
                    return;
                }

                // ── b. Duplicate check ───────────────────────────────────────
                $existing = Product::withTrashed()
                    ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                    ->first();

                if ($existing) {
                    if (! $isForce) {
                        $skipped++;
                        return;
                    }

                    // Update existing
                    if (! $isDryRun) {
                        DB::transaction(function () use ($existing, $name, $description, $price, $numberOfCourses) {
                            $existing->update([
                                'name'        => $name,
                                'description' => $description,
                                'price'       => $price,
                                'is_active'   => false,
                                // restore if soft-deleted
                                'deleted_at'  => null,
                            ]);

                            CourseProduct::updateOrCreate(
                                ['product_id'       => $existing->id],
                                ['number_of_courses' => $numberOfCourses]
                            );
                        });
                    }

                    $updated++;
                    return;
                }

                // ── c. Create new ────────────────────────────────────────────
                if (! $isDryRun) {
                    DB::transaction(function () use ($name, $description, $price, $numberOfCourses) {
                        $product = Product::create([
                            'type'        => 'course',
                            'name'        => $name,
                            'description' => $description,
                            'price'       => $price,
                            'is_active'   => false,
                        ]);

                        CourseProduct::create([
                            'product_id'       => $product->id,
                            'number_of_courses' => $numberOfCourses,
                        ]);
                    });
                }

                $created++;

            } catch (\Throwable $e) {
                $errors[] = ["#{$index}", $item['id'] ?? '?', $e->getMessage()];
            }
        });

        $this->output->newLine(2);

        // ── 5. Summary ───────────────────────────────────────────────────────
        $label = $isDryRun ? ' (dry run)' : '';

        $this->table(
            ['Result', 'Count'],
            [
                ['Created' . $label, $created],
                ['Updated' . $label, $updated],
                ['Skipped (already exist)', $skipped],
                ['Errors', count($errors)],
            ]
        );

        if (! empty($errors)) {
            $this->output->newLine();
            $this->warn('Errors encountered:');
            $this->table(['Index', 'JSON ID', 'Message'], $errors);
        }

        $this->info('Import complete.');

        return 0;
    }
}
