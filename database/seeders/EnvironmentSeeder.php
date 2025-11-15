<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Environment;

class EnvironmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $environments = [
            [
                'name' => 'Development',
                'slug' => 'development',
                'server_base_path' => 'C:\\xampp\\htdocs\\dev',
                'server_unc_path' => '\\\\10.10.15.59\\c$\\xampp\\htdocs\\dep_env_dev',
                'web_base_url' => 'http://dev-101-php-01.fmdqgroup.com',
                'deploy_endpoint_base' => 'http://101-php-01.fmdqgroup.com/dep_env_dev',
                'description' => 'Development environment for testing new features',
                'is_active' => true,
                'order' => 1,
            ],
            [
                'name' => 'Staging',
                'slug' => 'staging',
                'server_base_path' => 'C:\\xampp\\htdocs\\staging',
                'server_unc_path' => '\\\\10.10.15.59\\c$\\xampp\\htdocs\\dep_env_staging',
                'web_base_url' => 'http://staging-101-php-01.fmdqgroup.com',
                'deploy_endpoint_base' => 'http://101-php-01.fmdqgroup.com/dep_env_staging',
                'description' => 'Staging environment for pre-production testing',
                'is_active' => true,
                'order' => 2,
            ],
            [
                'name' => 'Production',
                'slug' => 'production',
                'server_base_path' => 'C:\\xampp\\htdocs\\prod',
                'server_unc_path' => '\\\\10.10.15.59\\c$\\xampp\\htdocs\\dep_env',
                'web_base_url' => 'http://101-php-01.fmdqgroup.com',
                'deploy_endpoint_base' => 'http://101-php-01.fmdqgroup.com/dep_env',
                'description' => 'Production environment for live applications',
                'is_active' => true,
                'order' => 3,
            ],
            // Add your custom environments here
            [
                'name' => 'QA',
                'slug' => 'qa',
                'server_base_path' => 'C:\\xampp\\htdocs\\qa',
                'server_unc_path' => '\\\\10.10.15.59\\c$\\xampp\\htdocs\\dep_env_qa',
                'web_base_url' => 'http://qa-101-php-01.fmdqgroup.com',
                'deploy_endpoint_base' => 'http://101-php-01.fmdqgroup.com/dep_env_qa',
                'description' => 'QA environment for quality assurance testing',
                'is_active' => true,
                'order' => 4,
            ],
        ];

        foreach ($environments as $environment) {
            Environment::updateOrCreate(
                ['slug' => $environment['slug']],
                $environment
            );
        }
    }
}
