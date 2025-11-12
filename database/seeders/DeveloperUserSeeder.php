<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DeveloperUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create developer user for deployment manager
        $developer = User::firstOrCreate([
            'email' => 'developer@deployment-manager.com',
        ], [
            'name' => 'Deployment Developer',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);
        
        // Ensure developer has the developer role
        if (!$developer->hasRole('developer')) {
            $developer->assignRole('developer');
        }
        
        $this->command->info('Developer user created successfully!');
        $this->command->info('Email: developer@deployment-manager.com');
        $this->command->info('Password: password');
    }
}