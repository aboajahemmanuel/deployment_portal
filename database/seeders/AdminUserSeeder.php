<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user for deployment manager
        $admin = User::firstOrCreate([
            'email' => 'admin@deployment-manager.com',
        ], [
            'name' => 'Deployment Admin',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        
        // Ensure admin has the admin role
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
        
        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@deployment-manager.com');
        $this->command->info('Password: password');
    }
}