<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;

class FixViewCachePath extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'view:fix-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix the view cache path issue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $viewPath = storage_path('framework/views');
        
        // Create the directory if it doesn't exist
        if (!File::isDirectory($viewPath)) {
            File::makeDirectory($viewPath, 0755, true);
            $this->info("Created view cache directory: {$viewPath}");
        }
        
        // Ensure proper permissions
        chmod($viewPath, 0755);
        $this->info("Set permissions for: {$viewPath}");
        
        // Clear compiled views
        if (File::exists($viewPath)) {
            $files = File::glob($viewPath.'/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    File::delete($file);
                }
            }
            $this->info('Cleared compiled views.');
        }
        
        // Create a .gitkeep file to ensure the directory is tracked by Git
        File::put($viewPath.'/.gitkeep', '');
        
        $this->info('View cache path has been fixed.');
        
        return 0;
    }
}
