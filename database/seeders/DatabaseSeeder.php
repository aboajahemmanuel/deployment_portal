<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run all seeders
        $this->call([
            EnvironmentSeeder::class,
            UserSeeder::class,
            AdminUserSeeder::class,
            DeveloperUserSeeder::class,
        ]);
    }
}