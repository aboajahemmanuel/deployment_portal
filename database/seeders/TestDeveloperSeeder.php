<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class TestDeveloperSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::create([
            'name' => 'Developer User',
            'email' => 'developer@example.com',
            'password' => bcrypt('password'),
        ]);
        
        $role = Role::where('name', 'developer')->first();
        $user->assignRole($role);
    }
}