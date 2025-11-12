<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\User;
use App\Services\Contracts\DeploymentServiceInterface;
use App\Services\Contracts\PipelineServiceInterface;
use App\Services\Contracts\NotificationServiceInterface;
use App\Services\Contracts\SecurityServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;

class DeploymentService implements DeploymentServiceInterface
{
    public function __construct(
        private PipelineServiceInterface $pipelineService,
        private NotificationServiceInterface $notificationService,
        private SecurityServiceInterface $securityService,
        private DeploymentLogger $logger
    ) {}

    /**
     * Create a new deployment for a project.
     */
    public function createDeployment(Project $project, User $user, array $options = []): Deployment
    {
        $deployment = Deployment::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'commit_hash' => $options['commit_hash'] ?? 'pending',
            'status' => 'pending',
            'started_at' => now(),
            'is_rollback' => $options['is_rollback'] ?? false,
            'rollback_target_id' => $options['rollback_target_id'] ?? null,
            'rollback_reason' => $options['rollback_reason'] ?? null,
        ]);

        // Create pipeline stages
        $template = $options['pipeline_template'] ?? 'default';
        $this->pipelineService->createPipeline($deployment, $template);

        Log::info('Deployment created', [
            'deployment_id' => $deployment->id,
            'project_id' => $project->id,
            'user_id' => $user->id,
            'is_rollback' => $deployment->is_rollback
        ]);

        return $deployment;
    }

    /**
     * Execute a deployment.
     */
    public function executeDeployment(Deployment $deployment): bool
    {
        try {
            $deployment->update(['status' => 'processing']);
            $logger = new DeploymentLogger($deployment);
            $project = $deployment->project;

            $logger->info('Starting deployment execution', [
                'deployment_id' => $deployment->id,
                'project_name' => $project->name,
                'deploy_endpoint' => $project->deploy_endpoint,
            ]);

            // Prepare request parameters
            $params = [
                'project_id' => $project->id,
                'branch' => $project->current_branch,
                'user_id' => $deployment->user_id,
                'deployment_id' => $deployment->id,
            ];

            // Execute deployment request
            $response = $this->makeDeploymentRequest($project, $params, $logger);

            if ($response['success']) {
                $deployment->update([
                    'status' => 'success',
                    'completed_at' => now(),
                    'log_output' => $response['output'],
                    'commit_hash' => $response['commit_hash'],
                ]);

                // Run post-deployment security scan
                $this->runPostDeploymentTasks($deployment, $logger);

                $this->notificationService->sendDeploymentNotification($deployment, 'success');
                return true;
            } else {
                $deployment->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'log_output' => $response['output'],
                ]);

                $this->notificationService->sendDeploymentNotification($deployment, 'failure');
                return false;
            }

        } catch (Exception $e) {
            $deployment->update([
                'status' => 'failed',
                'completed_at' => now(),
                'log_output' => json_encode([
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]),
            ]);

            Log::error('Deployment execution failed', [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage()
            ]);

            $this->notificationService->sendDeploymentNotification($deployment, 'failure');
            return false;
        }
    }

    /**
     * Create a rollback deployment.
     */
    public function createRollback(Project $project, Deployment $targetDeployment, User $user, string $reason = ''): Deployment
    {
        if ($targetDeployment->status !== 'success') {
            throw new Exception('Cannot rollback to a failed deployment');
        }

        if ($targetDeployment->project_id !== $project->id) {
            throw new Exception('Invalid deployment target for rollback');
        }

        if (empty($targetDeployment->commit_hash)) {
            throw new Exception('Target deployment has no commit hash recorded');
        }

        return $this->createDeployment($project, $user, [
            'is_rollback' => true,
            'rollback_target_id' => $targetDeployment->id,
            'rollback_reason' => $reason ?: 'Rollback initiated by user',
            'commit_hash' => $targetDeployment->commit_hash,
        ]);
    }

    /**
     * Execute a rollback deployment.
     */
    public function executeRollback(Deployment $rollbackDeployment): bool
    {
        if (!$rollbackDeployment->is_rollback) {
            throw new Exception('Not a rollback deployment');
        }

        $project = $rollbackDeployment->project;
        $targetDeployment = $rollbackDeployment->rollbackTarget;

        if (!$project->rollback_endpoint) {
            throw new Exception('No rollback endpoint configured for this project');
        }

        try {
            $rollbackDeployment->update(['status' => 'processing']);
            $logger = new DeploymentLogger($rollbackDeployment);

            $params = [
                'project_id' => $project->id,
                'branch' => $project->current_branch,
                'user_id' => $rollbackDeployment->user_id,
                'deployment_id' => $rollbackDeployment->id,
                'rollback' => true,
                'rollback_target_commit' => $targetDeployment->commit_hash,
                'rollback_reason' => $rollbackDeployment->rollback_reason,
            ];

            $response = $this->makeRollbackRequest($project, $params, $logger);

            if ($response['success']) {
                $rollbackDeployment->update([
                    'status' => 'success',
                    'completed_at' => now(),
                    'log_output' => $response['output'],
                    'commit_hash' => $response['commit_hash'],
                ]);

                $this->notificationService->sendDeploymentNotification($rollbackDeployment, 'success');
                return true;
            } else {
                $rollbackDeployment->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'log_output' => $response['output'],
                ]);

                $this->notificationService->sendDeploymentNotification($rollbackDeployment, 'failure');
                return false;
            }

        } catch (Exception $e) {
            $rollbackDeployment->update([
                'status' => 'failed',
                'completed_at' => now(),
                'log_output' => json_encode([
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]),
            ]);

            Log::error('Rollback execution failed', [
                'deployment_id' => $rollbackDeployment->id,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get deployment statistics for a project.
     */
    public function getDeploymentStats(Project $project): array
    {
        $deployments = $project->deployments();

        return [
            'total' => $deployments->count(),
            'successful' => $deployments->where('status', 'success')->count(),
            'failed' => $deployments->where('status', 'failed')->count(),
            'pending' => $deployments->where('status', 'pending')->count(),
            'rollbacks' => $deployments->where('is_rollback', true)->count(),
            'last_deployment' => $project->latestDeployment,
            'success_rate' => $this->calculateSuccessRate($deployments),
            'average_duration' => $this->calculateAverageDuration($deployments),
        ];
    }

    /**
     * Get recent deployments with pagination.
     */
    public function getRecentDeployments(int $limit = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $user = Auth::user();
        
        $query = Deployment::with(['project', 'user']);
        
        // Filter based on user role
        if ($user && !$user->hasRole('admin')) {
            $query->whereHas('project', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        return $query->latest()->paginate($limit);
    }

    /**
     * Cancel a pending deployment.
     */
    public function cancelDeployment(Deployment $deployment): bool
    {
        if ($deployment->status !== 'pending') {
            return false;
        }

        $deployment->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        $this->notificationService->sendDeploymentNotification($deployment, 'cancelled');
        return true;
    }

    /**
     * Get deployment logs.
     */
    public function getDeploymentLogs(Deployment $deployment): array
    {
        return [
            'deployment_log' => $deployment->log_output,
            'detailed_logs' => $deployment->logs()->orderBy('created_at')->get()->toArray(),
            'pipeline_stages' => $deployment->pipelineStages()->orderBy('order')->get()->toArray(),
        ];
    }

    /**
     * Make deployment HTTP request.
     */
    private function makeDeploymentRequest(Project $project, array $params, DeploymentLogger $logger): array
    {
        $logger->logHttpRequest($project->deploy_endpoint, 'GET', [], $params);

        $response = Http::withToken($project->access_token)
            ->timeout(300)
            ->withOptions(['verify' => false])
            ->get($project->deploy_endpoint, $params);

        $logger->logHttpResponse($response->status(), $response->body(), $response->headers());

        if ($response->successful()) {
            $commitHash = $this->extractCommitHash($response->body());
            return [
                'success' => true,
                'output' => $response->body(),
                'commit_hash' => $commitHash,
            ];
        }

        return [
            'success' => false,
            'output' => json_encode([
                'status_code' => $response->status(),
                'response_body' => $response->body(),
            ]),
            'commit_hash' => null,
        ];
    }

    /**
     * Make rollback HTTP request.
     */
    private function makeRollbackRequest(Project $project, array $params, DeploymentLogger $logger): array
    {
        $logger->logHttpRequest($project->rollback_endpoint, 'POST', ['Content-Type' => 'application/json'], $params);

        $response = Http::timeout(300)
            ->withOptions(['verify' => false])
            ->withHeaders(['Authorization' => 'Bearer test-token-123'])
            ->asJson()
            ->post($project->rollback_endpoint, $params);

        $logger->logHttpResponse($response->status(), $response->body(), $response->headers());

        if ($response->successful()) {
            $commitHash = $this->extractCommitHash($response->body()) ?? $params['rollback_target_commit'];
            return [
                'success' => true,
                'output' => $response->body(),
                'commit_hash' => $commitHash,
            ];
        }

        return [
            'success' => false,
            'output' => json_encode([
                'status_code' => $response->status(),
                'response_body' => $response->body(),
            ]),
            'commit_hash' => null,
        ];
    }

    /**
     * Extract commit hash from response.
     */
    private function extractCommitHash(string $responseBody): ?string
    {
        // Try JSON first
        $responseData = json_decode($responseBody, true);
        if (is_array($responseData) && isset($responseData['commit_hash'])) {
            return $responseData['commit_hash'];
        }

        // Try regex patterns
        if (preg_match('/([a-f0-9]{40})/', $responseBody, $matches)) {
            return $matches[1];
        }

        if (preg_match('/Updating [a-f0-9]{7,40}\.\.([a-f0-9]{7,40})/', $responseBody, $matches)) {
            return $matches[1];
        }

        if (preg_match('/HEAD is now at ([a-f0-9]{7,40})/', $responseBody, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Run post-deployment tasks.
     */
    private function runPostDeploymentTasks(Deployment $deployment, DeploymentLogger $logger): void
    {
        try {
            // Update pipeline stages
            $this->pipelineService->simulateExecution($deployment);

            // Run security scan
            $logger->info('Starting post-deployment security scan');
            $scanResult = $this->securityService->scanDeployment($deployment);
            
            if (!$scanResult->can_deploy) {
                $deployment->update(['status' => 'success_with_security_warnings']);
                $logger->warning('Security scan found critical issues', [
                    'violation_message' => $scanResult->violation_message
                ]);
            }

        } catch (Exception $e) {
            $logger->error('Post-deployment tasks failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Calculate success rate for deployments.
     */
    private function calculateSuccessRate($deployments): float
    {
        $total = $deployments->whereIn('status', ['success', 'failed'])->count();
        if ($total === 0) return 0;
        
        $successful = $deployments->where('status', 'success')->count();
        return round(($successful / $total) * 100, 2);
    }

    /**
     * Calculate average deployment duration.
     */
    private function calculateAverageDuration($deployments): int
    {
        $completed = $deployments->whereNotNull('completed_at')->get();
        if ($completed->isEmpty()) return 0;
        
        $totalDuration = $completed->sum(function ($deployment) {
            return $deployment->started_at->diffInSeconds($deployment->completed_at);
        });
        
        return round($totalDuration / $completed->count());
    }
}
