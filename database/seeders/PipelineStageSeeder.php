<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Deployment;
use App\Models\PipelineStage;

class PipelineStageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $deployments = Deployment::all();
        
        $stageTemplates = [
            ['name' => 'checkout', 'display_name' => 'Code Checkout', 'order' => 1],
            ['name' => 'build', 'display_name' => 'Build', 'order' => 2],
            ['name' => 'test', 'display_name' => 'Run Tests', 'order' => 3],
            ['name' => 'security_scan', 'display_name' => 'Security Scan', 'order' => 4],
            ['name' => 'deploy', 'display_name' => 'Deploy', 'order' => 5],
            ['name' => 'health_check', 'display_name' => 'Health Check', 'order' => 6],
        ];

        foreach ($deployments as $deployment) {
            foreach ($stageTemplates as $template) {
                // Simulate realistic success rates
                $successRate = match($template['name']) {
                    'checkout' => 0.98, // Very high success
                    'build' => 0.95,    // High success
                    'test' => 0.88,     // Medium-high success
                    'security_scan' => 0.92, // High success
                    'deploy' => 0.85,   // Medium success
                    'health_check' => 0.90, // High success
                    default => 0.90
                };

                $isSuccess = (rand(1, 100) / 100) <= $successRate;
                $status = $isSuccess ? 'success' : 'failed';
                
                // Generate realistic timestamps
                $startedAt = $deployment->created_at->addMinutes($template['order'] * 2);
                $duration = rand(30, 300); // 30 seconds to 5 minutes
                $completedAt = $startedAt->copy()->addSeconds($duration);

                PipelineStage::create([
                    'deployment_id' => $deployment->id,
                    'name' => $template['name'],
                    'display_name' => $template['display_name'],
                    'description' => "Automated {$template['display_name']} stage",
                    'order' => $template['order'],
                    'status' => $status,
                    'started_at' => $startedAt,
                    'completed_at' => $completedAt,
                    'duration' => $duration,
                    'output' => $isSuccess ? 
                        "✅ {$template['display_name']} completed successfully" : 
                        "❌ {$template['display_name']} failed with errors",
                    'error_message' => $isSuccess ? null : "Sample error for {$template['name']} stage",
                    'metadata' => [
                        'stage_type' => $template['name'],
                        'automated' => true,
                        'retry_count' => $isSuccess ? 0 : rand(1, 3)
                    ]
                ]);
            }
        }

        $this->command->info('Pipeline stages seeded successfully!');
    }
}
