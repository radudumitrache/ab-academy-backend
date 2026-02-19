<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CreateStorageDirectories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create required storage directories with proper permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $directories = [
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('framework/cache'),
            storage_path('framework/cache/data'),
            storage_path('logs'),
            storage_path('app/public'),
        ];

        foreach ($directories as $directory) {
            if (!File::isDirectory($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->info("Created directory: {$directory}");
            } else {
                $this->comment("Directory already exists: {$directory}");
            }
            
            // Ensure proper permissions
            chmod($directory, 0755);
            $this->info("Set permissions for: {$directory}");
        }

        $this->info('All storage directories have been created and permissions set.');
        
        return 0;
    }
}
