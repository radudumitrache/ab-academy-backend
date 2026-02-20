<?php

namespace App\Console\Commands;

use App\Models\Admin;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create 
                            {--username= : The username for the admin user}
                            {--email= : The email for the admin user}
                            {--telephone= : The telephone number for the admin user (optional)}
                            {--password= : The password for the admin user}
                            {--super : Whether this admin should be a super admin}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new admin user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get options or prompt for them
        $username = $this->option('username') ?: $this->ask('Enter username');
        $email = $this->option('email') ?: $this->ask('Enter email');
        $telephone = $this->option('telephone') ?: $this->ask('Enter telephone (optional)');
        $password = $this->option('password') ?: $this->secret('Enter password');
        $isSuperAdmin = $this->option('super');
        
        // Validate input
        $validator = Validator::make([
            'username' => $username,
            'email' => $email,
            'telephone' => $telephone,
            'password' => $password,
        ], [
            'username' => 'required|string|min:3|max:50|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'telephone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return 1;
        }

        try {
            // Create the admin user
            $admin = new Admin();
            $admin->username = $username;
            $admin->email = $email;
            $admin->telephone = $telephone ?: null;
            $admin->password = Hash::make($password);
            $admin->role = $isSuperAdmin ? 'super_admin' : 'admin';
            $admin->save();

            $this->info('Admin user created successfully!');
            $this->table(
                ['ID', 'Username', 'Email', 'Telephone', 'Role'],
                [[$admin->id, $admin->username, $admin->email, $admin->telephone ?: 'N/A', $admin->role]]
            );
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to create admin user: ' . $e->getMessage());
            return 1;
        }
    }
}
