<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\PipelineStage;
use App\Models\Project;
use App\Services\Contracts\PipelineServiceInterface;

class PipelineService implements PipelineServiceInterface
{
    /**
     * Create pipeline stages for a deployment.
     */
    public function createPipeline(Deployment $deployment, ?string $template = 'default'): void
    {
        $stages = match($template) {
            'web_app' => $this->getPipelineTemplates()['web_app']['stages'],
            'api' => $this->getPipelineTemplates()['api']['stages'],
            'microservice' => $this->getPipelineTemplates()['microservice']['stages'],
            default => $this->getDefaultStages()
        };
        
        foreach ($stages as $index => $stageData) {
            PipelineStage::create([
                'deployment_id' => $deployment->id,
                'name' => $stageData['name'],
                'display_name' => $stageData['display_name'],
                'description' => $stageData['description'],
                'order' => $index + 1,
                'status' => 'pending',
            ]);
        }
    }

    /**
     * Create default pipeline stages for a deployment.
     */
    public function createDefaultPipeline(Deployment $deployment): void
    {
        $this->createPipeline($deployment, 'default');
    }

    /**
     * Create custom pipeline stages based on project configuration.
     */
    public function createCustomPipeline(Deployment $deployment, array $stages): void
    {
        foreach ($stages as $index => $stageData) {
            PipelineStage::create([
                'deployment_id' => $deployment->id,
                'name' => $stageData['name'],
                'display_name' => $stageData['display_name'],
                'description' => $stageData['description'] ?? '',
                'order' => $index + 1,
                'status' => 'pending',
                'metadata' => $stageData['metadata'] ?? null,
            ]);
        }
    }

    /**
     * Get default pipeline stages configuration.
     */
    public function getDefaultStages(): array
    {
        return [
            [
                'name' => 'preparation',
                'display_name' => 'Code Preparation',
                'description' => 'Preparing code repository and validating deployment requirements',
            ],
            [
                'name' => 'build',
                'display_name' => 'Build & Compile',
                'description' => 'Building application and compiling assets',
            ],
            [
                'name' => 'test',
                'display_name' => 'Testing',
                'description' => 'Running automated tests and quality checks',
            ],
            [
                'name' => 'deploy',
                'display_name' => 'Deployment',
                'description' => 'Deploying application to target environment',
            ],
            [
                'name' => 'verify',
                'display_name' => 'Verification',
                'description' => 'Verifying deployment success and running health checks',
            ],
        ];
    }

    /**
     * Get pipeline templates for different project types.
     */
    public function getPipelineTemplates(): array
    {
        return [
            'basic' => [
                'name' => 'Basic Deployment',
                'description' => 'Simple deployment pipeline for basic applications',
                'stages' => $this->getDefaultStages(),
            ],
            'web_app' => [
                'name' => 'Web Application',
                'description' => 'Pipeline for web applications with asset compilation',
                'stages' => [
                    [
                        'name' => 'preparation',
                        'display_name' => 'Code Preparation',
                        'description' => 'Preparing code repository and dependencies',
                    ],
                    [
                        'name' => 'dependencies',
                        'display_name' => 'Install Dependencies',
                        'description' => 'Installing npm/composer dependencies',
                    ],
                    [
                        'name' => 'build',
                        'display_name' => 'Build Assets',
                        'description' => 'Compiling CSS, JS and other assets',
                    ],
                    [
                        'name' => 'test',
                        'display_name' => 'Run Tests',
                        'description' => 'Running unit and integration tests',
                    ],
                    [
                        'name' => 'deploy',
                        'display_name' => 'Deploy Application',
                        'description' => 'Deploying to production environment',
                    ],
                    [
                        'name' => 'cache',
                        'display_name' => 'Clear Cache',
                        'description' => 'Clearing application cache',
                    ],
                    [
                        'name' => 'verify',
                        'display_name' => 'Health Check',
                        'description' => 'Verifying application health and availability',
                    ],
                ],
            ],
            'api' => [
                'name' => 'API Service',
                'description' => 'Pipeline optimized for API deployments',
                'stages' => [
                    [
                        'name' => 'preparation',
                        'display_name' => 'Code Preparation',
                        'description' => 'Preparing API code and configuration',
                    ],
                    [
                        'name' => 'dependencies',
                        'display_name' => 'Install Dependencies',
                        'description' => 'Installing required packages',
                    ],
                    [
                        'name' => 'database',
                        'display_name' => 'Database Migration',
                        'description' => 'Running database migrations',
                    ],
                    [
                        'name' => 'test',
                        'display_name' => 'API Testing',
                        'description' => 'Running API tests and validation',
                    ],
                    [
                        'name' => 'deploy',
                        'display_name' => 'Deploy API',
                        'description' => 'Deploying API to production',
                    ],
                    [
                        'name' => 'verify',
                        'display_name' => 'API Health Check',
                        'description' => 'Testing API endpoints and performance',
                    ],
                ],
            ],
            'microservice' => [
                'name' => 'Microservice',
                'description' => 'Pipeline for microservice deployments',
                'stages' => [
                    [
                        'name' => 'preparation',
                        'display_name' => 'Code Preparation',
                        'description' => 'Preparing microservice code',
                    ],
                    [
                        'name' => 'build',
                        'display_name' => 'Build Service',
                        'description' => 'Building microservice container',
                    ],
                    [
                        'name' => 'test',
                        'display_name' => 'Service Testing',
                        'description' => 'Running service-specific tests',
                    ],
                    [
                        'name' => 'integration',
                        'display_name' => 'Integration Tests',
                        'description' => 'Testing service integration',
                    ],
                    [
                        'name' => 'deploy',
                        'display_name' => 'Deploy Service',
                        'description' => 'Deploying microservice',
                    ],
                    [
                        'name' => 'register',
                        'display_name' => 'Service Registration',
                        'description' => 'Registering service with discovery',
                    ],
                    [
                        'name' => 'verify',
                        'display_name' => 'Service Verification',
                        'description' => 'Verifying service health and connectivity',
                    ],
                ],
            ],
        ];
    }

    /**
     * Execute next pipeline stage.
     */
    public function executeNextStage(Deployment $deployment): ?PipelineStage
    {
        $nextStage = $deployment->pipelineStages()
            ->where('status', 'pending')
            ->orderBy('order')
            ->first();

        if ($nextStage) {
            $nextStage->markAsStarted();
            return $nextStage;
        }

        return null;
    }

    /**
     * Complete a stage and advance pipeline.
     */
    public function completeStage(PipelineStage $stage, bool $success = true, ?string $output = null, ?string $errorMessage = null): ?PipelineStage
    {
        if ($success) {
            $stage->markAsSuccess($output);
        } else {
            $stage->markAsFailed($errorMessage, $output);
            return null; // Stop pipeline on failure
        }

        // Start next stage
        return $this->executeNextStage($stage->deployment);
    }

    /**
     * Complete current stage and advance pipeline.
     */
    public function completeStageAndAdvance(PipelineStage $stage, bool $success = true, string $output = null, string $errorMessage = null): ?PipelineStage
    {
        if ($success) {
            $stage->markAsSuccess($output);
        } else {
            $stage->markAsFailed($errorMessage, $output);
            return null; // Stop pipeline on failure
        }

        // Start next stage
        return $this->executeNextStage($stage->deployment);
    }

    /**
     * Get pipeline analytics for a project.
     */
    public function getProjectAnalytics(Project $project): array
    {
        return $this->getProjectPipelineAnalytics($project);
    }

    /**
     * Get pipeline analytics for a project.
     */
    public function getProjectPipelineAnalytics(Project $project): array
    {
        $deployments = $project->deployments()->with('pipelineStages')->get();
        
        $analytics = [
            'total_deployments' => $deployments->count(),
            'successful_deployments' => $deployments->where('status', 'success')->count(),
            'failed_deployments' => $deployments->where('status', 'failed')->count(),
            'average_duration' => 0,
            'stage_success_rates' => [],
            'common_failure_stages' => [],
            'deployment_frequency' => [],
        ];

        if ($deployments->isEmpty()) {
            return $analytics;
        }

        // Calculate average deployment duration
        $completedDeployments = $deployments->whereNotNull('completed_at');
        if ($completedDeployments->count() > 0) {
            $totalDuration = $completedDeployments->sum(function ($deployment) {
                return $deployment->started_at->diffInSeconds($deployment->completed_at);
            });
            $analytics['average_duration'] = round($totalDuration / $completedDeployments->count());
        }

        // Calculate stage success rates
        $allStages = $deployments->flatMap->pipelineStages;
        $stageGroups = $allStages->groupBy('name');
        
        foreach ($stageGroups as $stageName => $stages) {
            $total = $stages->count();
            $successful = $stages->where('status', 'success')->count();
            $analytics['stage_success_rates'][$stageName] = [
                'total' => $total,
                'successful' => $successful,
                'rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
            ];
        }

        // Find common failure stages
        $failedStages = $allStages->where('status', 'failed')->groupBy('name');
        foreach ($failedStages as $stageName => $stages) {
            $analytics['common_failure_stages'][$stageName] = $stages->count();
        }
        arsort($analytics['common_failure_stages']);

        return $analytics;
    }

    /**
     * Get system-wide pipeline metrics.
     */
    public function getSystemMetrics(): array
    {
        return $this->getSystemPipelineMetrics();
    }

    /**
     * Get system-wide pipeline metrics.
     */
    public function getSystemPipelineMetrics(): array
    {
        $allDeployments = Deployment::with('pipelineStages')->get();
        
        return [
            'total_pipelines' => $allDeployments->count(),
            'active_pipelines' => $allDeployments->where('status', 'running')->count(),
            'success_rate' => $this->calculateSuccessRate($allDeployments),
            'average_pipeline_duration' => $this->calculateAverageDuration($allDeployments),
            'most_common_failures' => $this->getMostCommonFailures($allDeployments),
            'pipeline_trends' => $this->getPipelineTrends($allDeployments),
        ];
    }

    /**
     * Calculate overall success rate.
     */
    private function calculateSuccessRate($deployments): float
    {
        $total = $deployments->whereIn('status', ['success', 'failed'])->count();
        if ($total === 0) return 0;
        
        $successful = $deployments->where('status', 'success')->count();
        return round(($successful / $total) * 100, 2);
    }

    /**
     * Calculate average pipeline duration.
     */
    private function calculateAverageDuration($deployments): int
    {
        $completed = $deployments->whereNotNull('completed_at');
        if ($completed->isEmpty()) return 0;
        
        $totalDuration = $completed->sum(function ($deployment) {
            return $deployment->started_at->diffInSeconds($deployment->completed_at);
        });
        
        return round($totalDuration / $completed->count());
    }

    /**
     * Get most common failure points.
     */
    private function getMostCommonFailures($deployments): array
    {
        $failedStages = $deployments->flatMap->pipelineStages
            ->where('status', 'failed')
            ->groupBy('name')
            ->map->count()
            ->sortDesc()
            ->take(5);
            
        return $failedStages->toArray();
    }

    /**
     * Get pipeline execution trends.
     */
    private function getPipelineTrends($deployments): array
    {
        $trends = [];
        $deploymentsByDate = $deployments->groupBy(function ($deployment) {
            return $deployment->created_at->format('Y-m-d');
        });
        
        foreach ($deploymentsByDate as $date => $dayDeployments) {
            $trends[$date] = [
                'total' => $dayDeployments->count(),
                'successful' => $dayDeployments->where('status', 'success')->count(),
                'failed' => $dayDeployments->where('status', 'failed')->count(),
            ];
        }
        
        return $trends;
    }

    /**
     * Simulate pipeline execution.
     */
    public function simulateExecution(Deployment $deployment): void
    {
        $stages = $deployment->pipelineStages()->orderBy('order')->get();
        
        foreach ($stages as $stage) {
            if ($stage->status === 'pending') {
                $stage->update(['status' => 'success', 'completed_at' => now()]);
            }
        }
    }

    /**
     * Cancel pipeline execution.
     */
    public function cancelPipeline(Deployment $deployment): bool
    {
        try {
            $deployment->pipelineStages()
                ->where('status', 'pending')
                ->update(['status' => 'cancelled', 'completed_at' => now()]);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
