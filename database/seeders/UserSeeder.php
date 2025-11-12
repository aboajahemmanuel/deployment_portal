<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles if they don't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $developerRole = Role::firstOrCreate(['name' => 'developer']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Create Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@deployment.local'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole($adminRole);

        // Create Developer Users
        $developer1 = User::firstOrCreate(
            ['email' => 'john.doe@deployment.local'],
            [
                'name' => 'John Doe',
                'password' => Hash::make('developer123'),
                'email_verified_at' => now(),
            ]
        );
        $developer1->assignRole($developerRole);

        $developer2 = User::firstOrCreate(
            ['email' => 'jane.smith@deployment.local'],
            [
                'name' => 'Jane Smith',
                'password' => Hash::make('developer123'),
                'email_verified_at' => now(),
            ]
        );
        $developer2->assignRole($developerRole);

        // Create Regular User
        $user = User::firstOrCreate(
            ['email' => 'user@deployment.local'],
            [
                'name' => 'Regular User',
                'password' => Hash::make('user123'),
                'email_verified_at' => now(),
            ]
        );
        $user->assignRole($userRole);

        $this->command->info('Users created successfully!');
        $this->command->info('Admin: admin@deployment.local / admin123');
        $this->command->info('Developer: john.doe@deployment.local / developer123');
        $this->command->info('Developer: jane.smith@deployment.local / developer123');
        $this->command->info('User: user@deployment.local / user123');
    }
}
