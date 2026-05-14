<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetUserPassword extends Command
{
    protected $signature = 'user:reset-password
                            {identifier : The user\'s email, username, or ID}
                            {--password= : The new password (omit to generate one)}';

    protected $description = 'Reset a user\'s password by email, username, or ID';

    public function handle()
    {
        $identifier = $this->argument('identifier');

        $user = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->orWhere('id', is_numeric($identifier) ? (int) $identifier : -1)
            ->first();

        if (!$user) {
            $this->error("No user found matching '{$identifier}'.");
            return 1;
        }

        $this->info("Found user: [{$user->id}] {$user->username} ({$user->email}) — role: {$user->role}");

        $newPassword = $this->option('password');
        $generated   = false;

        if (!$newPassword) {
            $newPassword = Str::password(16);
            $generated   = true;
        }

        if (strlen($newPassword) < 8) {
            $this->error('Password must be at least 8 characters.');
            return 1;
        }

        if (!$this->confirm("Reset password for {$user->username}?", true)) {
            $this->line('Aborted.');
            return 0;
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        $this->info('Password reset successfully.');

        if ($generated) {
            $this->table(['Field', 'Value'], [
                ['User ID',       $user->id],
                ['Username',      $user->username],
                ['Email',         $user->email],
                ['New Password',  $newPassword],
            ]);
            $this->warn('Save this password — it will not be shown again.');
        }

        return 0;
    }
}
