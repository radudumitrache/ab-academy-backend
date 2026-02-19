<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class FixPassportInstallation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'passport:fix-installation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix Passport installation issues by reinstalling keys and clients';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Passport installation fix...');

        // Check if migrations have been run
        if (!Schema::hasTable('oauth_clients')) {
            $this->error('OAuth tables not found. Please run migrations first:');
            $this->line('php artisan migrate');
            return 1;
        }

        // Clear existing keys
        $privateKeyPath = storage_path('oauth-private.key');
        $publicKeyPath = storage_path('oauth-public.key');
        
        if (File::exists($privateKeyPath)) {
            File::delete($privateKeyPath);
            $this->info('Removed existing private key.');
        }
        
        if (File::exists($publicKeyPath)) {
            File::delete($publicKeyPath);
            $this->info('Removed existing public key.');
        }

        // Generate new keys
        $this->info('Generating new Passport keys...');
        Artisan::call('passport:keys', ['--force' => true]);
        $this->info(Artisan::output());

        // Clear existing clients
        $this->info('Clearing existing OAuth clients...');
        DB::table('oauth_clients')->truncate();
        DB::table('oauth_personal_access_clients')->truncate();
        $this->info('OAuth clients cleared.');

        // Create new personal access client
        $this->info('Creating new personal access client...');
        Artisan::call('passport:client', [
            '--personal' => true,
            '--name' => 'Personal Access Client',
            '--quiet' => true
        ]);
        $this->info('Personal access client created.');

        // Create new password grant client
        $this->info('Creating new password grant client...');
        Artisan::call('passport:client', [
            '--password' => true,
            '--name' => 'Password Grant Client',
            '--quiet' => true
        ]);
        $this->info('Password grant client created.');

        // Clear tokens
        $this->info('Clearing existing OAuth tokens...');
        if (Schema::hasTable('oauth_access_tokens')) {
            DB::table('oauth_access_tokens')->truncate();
            $this->info('Access tokens cleared.');
        }
        
        if (Schema::hasTable('oauth_refresh_tokens')) {
            DB::table('oauth_refresh_tokens')->truncate();
            $this->info('Refresh tokens cleared.');
        }
        
        if (Schema::hasTable('oauth_auth_codes')) {
            DB::table('oauth_auth_codes')->truncate();
            $this->info('Auth codes cleared.');
        }

        $this->info('Passport installation has been fixed successfully!');
        $this->info('Users will need to log in again to get new tokens.');
        
        return 0;
    }
}
