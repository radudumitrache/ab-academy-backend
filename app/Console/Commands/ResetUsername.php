<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class ResetUsername extends Command
{
    protected $signature = 'user:reset-username
                            {identifier : The user\'s email, current username, or ID}
                            {--username= : The new username}';

    protected $description = 'Reset a user\'s username by email, current username, or ID';

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

        $newUsername = $this->option('username') ?: $this->ask('Enter new username');

        $validator = Validator::make(['username' => $newUsername], [
            'username' => 'required|string|min:3|max:50|unique:users,username,' . $user->id,
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        $oldUsername = $user->username;

        if (!$this->confirm("Change username from '{$oldUsername}' to '{$newUsername}'?", true)) {
            $this->line('Aborted.');
            return 0;
        }

        $user->username = $newUsername;
        $user->save();

        $this->info('Username updated successfully.');
        $this->table(['Field', 'Value'], [
            ['User ID',      $user->id],
            ['Old Username', $oldUsername],
            ['New Username', $user->username],
            ['Email',        $user->email],
            ['Role',         $user->role],
        ]);

        return 0;
    }
}
