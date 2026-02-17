<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

class CheckPassportKeys extends Command
{
    protected $signature = 'passport:check-keys';
    protected $description = 'Check if Passport keys exist and reinstall if needed';

    public function handle()
    {
        $this->info('Checking Passport keys...');
        
        $privateKeyPath = storage_path('oauth-private.key');
        $publicKeyPath = storage_path('oauth-public.key');
        
        if (!File::exists($privateKeyPath) || !File::exists($publicKeyPath)) {
            $this->warn('Passport keys not found. Installing new keys...');
            Artisan::call('passport:keys');
            $this->info('Passport keys installed successfully.');
        } else {
            $this->info('Passport keys already exist.');
        }
        
        $this->info('Checking Passport clients...');
        
        $hasClients = DB::table('oauth_clients')->count() > 0;
        
        if (!$hasClients) {
            $this->warn('No Passport clients found. Installing personal access client...');
            Artisan::call('passport:client', ['--personal' => true, '--name' => 'Personal Access Client']);
            $this->info('Personal access client installed successfully.');
        } else {
            $this->info('Passport clients already exist.');
        }
        
        $this->info('Passport check completed.');
    }
}
