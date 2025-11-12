<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SecurityScanResult;
use App\Models\Deployment;

class SecurityScanResultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some deployments to associate with scan results
        $deployments = Deployment::limit(5)->get();
        
        if ($deployments->isEmpty()) {
            $this->command->info('No deployments found. Please create some deployments first.');
            return;
        }

        $scanTypes = ['sast', 'dependency', 'secrets', 'infrastructure'];
        $severities = ['critical', 'high', 'medium', 'low'];
        $statuses = ['open', 'acknowledged', 'fixed'];

        $vulnerabilities = [
            [
                'title' => 'SQL Injection vulnerability in user input validation',
                'description' => 'User input is not properly sanitized before database queries',
                'severity' => 'critical',
                'scan_type' => 'sast',
                'file_path' => '/app/Http/Controllers/UserController.php',
                'line_number' => 45,
            ],
            [
                'title' => 'Outdated dependency with known security vulnerabilities',
                'description' => 'Package lodash@4.17.15 has known security vulnerabilities',
                'severity' => 'high',
                'scan_type' => 'dependency',
                'file_path' => '/package.json',
                'line_number' => null,
            ],
            [
                'title' => 'Hardcoded API key detected',
                'description' => 'API key found in source code: sk-1234567890abcdef',
                'severity' => 'critical',
                'scan_type' => 'secrets',
                'file_path' => '/config/services.php',
                'line_number' => 23,
            ],
            [
                'title' => 'Cross-Site Scripting (XSS) vulnerability',
                'description' => 'User input displayed without proper escaping',
                'severity' => 'high',
                'scan_type' => 'sast',
                'file_path' => '/resources/views/dashboard.blade.php',
                'line_number' => 67,
            ],
            [
                'title' => 'Weak password hashing algorithm',
                'description' => 'MD5 hashing detected, should use bcrypt or Argon2',
                'severity' => 'medium',
                'scan_type' => 'sast',
                'file_path' => '/app/Services/AuthService.php',
                'line_number' => 89,
            ],
            [
                'title' => 'Insecure HTTP connection',
                'description' => 'HTTP connection used instead of HTTPS for sensitive data',
                'severity' => 'medium',
                'scan_type' => 'infrastructure',
                'file_path' => '/config/app.php',
                'line_number' => 12,
            ],
            [
                'title' => 'Deprecated jQuery version',
                'description' => 'jQuery 2.1.4 has known security vulnerabilities',
                'severity' => 'low',
                'scan_type' => 'dependency',
                'file_path' => '/public/js/vendor.js',
                'line_number' => null,
            ],
            [
                'title' => 'Missing CSRF protection',
                'description' => 'Form submission without CSRF token validation',
                'severity' => 'high',
                'scan_type' => 'sast',
                'file_path' => '/resources/views/forms/contact.blade.php',
                'line_number' => 34,
            ]
        ];

        foreach ($deployments as $deployment) {
            // Create 2-4 scan results per deployment
            $numResults = rand(2, 4);
            $selectedVulns = collect($vulnerabilities)->random($numResults);
            
            foreach ($selectedVulns as $vuln) {
                SecurityScanResult::create([
                    'deployment_id' => $deployment->id,
                    'scan_type' => $vuln['scan_type'],
                    'tool_name' => 'security-scanner',
                    'severity' => $vuln['severity'],
                    'title' => $vuln['title'],
                    'description' => $vuln['description'],
                    'file_path' => $vuln['file_path'],
                    'line_number' => $vuln['line_number'],
                    'status' => collect($statuses)->random(),
                    'metadata' => json_encode([
                        'tool' => 'security-scanner',
                        'version' => '1.0.0',
                        'scan_time' => now()->toISOString(),
                        'confidence' => rand(70, 95) . '%'
                    ]),
                    'created_at' => now()->subDays(rand(0, 30)),
                    'updated_at' => now()->subDays(rand(0, 7)),
                ]);
            }
        }

        $this->command->info('Security scan results seeded successfully!');
    }
}
