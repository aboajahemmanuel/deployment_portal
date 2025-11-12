<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Project;

class TestProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Project::create([
            'name' => 'Test Laravel App',
            'repository_url' => 'https://github.com/example/test-laravel-app.git',
            'deploy_endpoint' => 'http://localhost/deployment-management/deploy.php',
            'rollback_endpoint' => 'http://localhost/deployment-management/rollback.php',
            'access_token' => 'test-token-123',
            'current_branch' => 'main',
            'description' => 'A test Laravel application for deployment testing',
            'is_active' => true,
        ]);
    }
}