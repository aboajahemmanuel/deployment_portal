<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\ScheduledDeployment;
use App\Models\Project;
use App\Models\Deployment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\DeploymentLogger;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use App\Notifications\DeploymentNotification;

class ProcessScheduledDeployment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $scheduledDeployment;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(ScheduledDeployment $scheduledDeployment)
    {
        $this->scheduledDeployment = $scheduledDeployment;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        // Refresh the model to get the latest data
        $scheduledDeployment = $this->scheduledDeployment->fresh();
        
        // Check if the deployment is still queued or pending (might have been cancelled)
        if (!in_array($scheduledDeployment->status, ['pending', 'queued'])) {
            Log::info("Scheduled deployment #{$scheduledDeployment->id} is no longer pending/queued (status: {$scheduledDeployment->status}), skipping.");
            return;
        }
        
        // Update status to processing
        $scheduledDeployment->update(['status' => 'processing']);
        
        // Get the project
        $project = $scheduledDeployment->project;
        
        if (!$project) {
            Log::error("Project not found for scheduled deployment #{$scheduledDeployment->id}");
            $scheduledDeployment->update([
                'status' => 'failed',
                'last_run_at' => now(),
                'queue_job_id' => null
            ]);
            return;
        }
        
        try {
            // Prepare deployment data
            $deploymentData = [
                'project_id' => $project->id,
                'branch' => $project->current_branch ?? 'main',
                'user_id' => $scheduledDeployment->user_id,
                'is_scheduled' => true,
                'scheduled_deployment_id' => $scheduledDeployment->id
            ];
            
            // Create a deployment record up front so we can attach structured logs
            $deployment = Deployment::create([
                'project_id' => $project->id,
                'user_id' => $scheduledDeployment->user_id,
                'commit_hash' => 'scheduled-' . now()->format('YmdHis'),
                'status' => 'processing',
                'started_at' => now(),
            ]);

            // Create pipeline stages for this scheduled deployment
            app(\App\Services\PipelineStageManager::class)->createStagesForDeployment($deployment);
            
            // Initialize a logger bound to this deployment
            $logger = new \App\Services\DeploymentLogger($deployment);
            $logger->info('Scheduled deployment attempt started', [
                'project_name' => $project->name ?? null,
                'deploy_endpoint' => $project->deploy_endpoint,
                'scheduled_deployment_id' => $scheduledDeployment->id,
            ]);
            
            // Log intent before making request (mask token)
            $maskedToken = $project->access_token ? substr($project->access_token, 0, 4) . '***' : null;
            Log::info('Scheduled deployment: sending request to deploy endpoint', [
                'scheduled_deployment_id' => $scheduledDeployment->id,
                'project_id' => $project->id,
                'endpoint' => $project->deploy_endpoint,
                'branch' => $deploymentData['branch'],
                'auth' => $maskedToken ? ('Bearer ' . $maskedToken) : null,
            ]);

            // Log the outgoing HTTP request to deployment logs as well
            $logger->logHttpRequest($project->deploy_endpoint, 'POST', [
                'Authorization' => $maskedToken ? ('Bearer ' . $maskedToken) : null,
                'Content-Type' => 'application/json',
            ], $deploymentData);

            // Make the deployment request with retry and timeouts
            $response = Http::retry(3, 2000)
                ->timeout(60)
                ->connectTimeout(10)
                ->asJson()
                ->withHeaders([
                    'Authorization' => 'Bearer ' . ($project->access_token ?? ''),
                ])
                ->post($project->deploy_endpoint, $deploymentData);
            
            $responseBody = (string) $response->body();
            $looksSuccessful = is_string($responseBody)
                && (
                    str_contains($responseBody, 'DEPLOYMENT_STATUS=success')
                    || str_contains($responseBody, '✅ Deployment finished successfully')
                    || str_contains($responseBody, 'Deployment started')
                )
                && !str_contains($responseBody, '❌ Command failed')
                && !str_contains(strtolower($responseBody), 'fatal error');
            $isSuccessful = $response->successful() || $looksSuccessful;

            // Log the response from endpoint for diagnostics
            Log::info('Scheduled deployment: received response from deploy endpoint', [
                'scheduled_deployment_id' => $scheduledDeployment->id,
                'http_status' => $response->status(),
                'successful' => $isSuccessful,
                'response_excerpt' => mb_substr($responseBody ?? '', 0, 500),
            ]);
            
            // Also log the HTTP response in deployment logs
            $logger->logHttpResponse($response->status(), (string) ($responseBody ?? ''), $response->headers());

            // Attempt to parse commit hash from response (same logic as manual deployment)
            $commitHash = null;
            $responseData = json_decode($responseBody ?? '', true);
            if (is_array($responseData) && isset($responseData['commit_hash'])) {
                $commitHash = $responseData['commit_hash'];
            } else {
                if (is_string($responseBody)) {
                    if (preg_match('/([a-f0-9]{40})/', $responseBody, $matches)) {
                        $commitHash = $matches[1];
                    } elseif (preg_match('/Updating [a-f0-9]{7,40}\.\.([a-f0-9]{7,40})/', $responseBody, $matches)) {
                        $commitHash = $matches[1];
                    } elseif (preg_match('/HEAD is now at ([a-f0-9]{7,40})/', $responseBody, $matches)) {
                        $commitHash = $matches[1];
                    }
                }
            }

            // Update the existing deployment record
            // Try to extract run id for traceability
            $runId = null;
            if (preg_match('/Run ID:\s*([0-9_\-]+)/', (string) $responseBody, $m)) {
                $runId = $m[1];
            }

            $deployment->update([
                'status' => $isSuccessful ? 'success' : 'failed',
                'log_output' => ($runId ? ("[run_id:".$runId."]\n") : '') . $responseBody,
                'completed_at' => now(),
                'commit_hash' => $commitHash ?? $deployment->commit_hash,
            ]);

            // Update pipeline stages to reflect deployment result
            $stageManager = app(\App\Services\PipelineStageManager::class);
            $stageManager->simulateExecution($deployment);

            // Log success/failure summary
            if ($isSuccessful) {
                $logger->info('Scheduled deployment successful', [
                    'deployment_id' => $deployment->id,
                    'project_id' => $project->id,
                    'commit_hash' => $commitHash,
                ]);
            } else {
                $logger->error('Scheduled deployment failed with HTTP error', [
                    'status_code' => $response->status(),
                    'project_id' => $project->id,
                    'deployment_id' => $deployment->id,
                ]);
            }
            
            // Update scheduled deployment status
            $updates = [
                'status' => $isSuccessful ? 'completed' : 'failed',
                'last_run_at' => now(),
                'queue_job_id' => null // Clear job ID after processing
            ];
            
            // If it's recurring and successful, calculate next run and reset for next execution
            if ($scheduledDeployment->is_recurring && $isSuccessful) {
                $nextRun = $this->calculateNextRun(
                    $scheduledDeployment->scheduled_at, 
                    $scheduledDeployment->recurrence_pattern
                );
                
                if ($nextRun) {
                    $updates['next_run_at'] = $nextRun;
                    $updates['scheduled_at'] = $nextRun; // Update scheduled_at for next execution
                    $updates['status'] = 'pending'; // Reset to pending for next run
                    $updates['queue_job_id'] = null; // Ensure job ID is cleared
                }
            }
            
            $scheduledDeployment->update($updates);
            
            Log::info("Completed scheduled deployment #{$scheduledDeployment->id} with status: " . $updates['status'], [
                'deployment_id' => $deployment->id,
                'project_id' => $project->id,
                'is_recurring' => $scheduledDeployment->is_recurring,
                'next_run_at' => $updates['next_run_at'] ?? null
            ]);

            // Send notifications via shared notifier service
            try {
                app(\App\Services\DeploymentNotifier::class)
                    ->send($deployment, $isSuccessful ? 'success' : 'failure');
            } catch (\Throwable $notifyEx) {
                Log::warning('Failed to send scheduled deployment notifications', [
                    'deployment_id' => $deployment->id,
                    'error' => $notifyEx->getMessage(),
                ]);
            }
            
        } catch (\Throwable $e) {
            Log::error("Failed to process scheduled deployment #{$scheduledDeployment->id}: " . $e->getMessage(), [
                'exception' => $e,
                'scheduled_deployment' => $scheduledDeployment,
                'project_id' => $project->id ?? null
            ]);
            // If a deployment record exists, attach exception log and mark failed
            if (isset($deployment) && $deployment) {
                try {
                    (new \App\Services\DeploymentLogger($deployment))->logException($e instanceof \Exception ? $e : new \Exception($e->getMessage(), 0, $e));
                    $deployment->update([
                        'status' => 'failed',
                        'log_output' => (($deployment->log_output ?? '') . "\nException: " . $e->getMessage()),
                        'completed_at' => now(),
                    ]);
                } catch (\Throwable $ignored) {
                    // swallow
                }
            }
            
            // Mark as failed and clear job ID
            $scheduledDeployment->update([
                'status' => 'failed',
                'last_run_at' => now(),
                'queue_job_id' => null
            ]);
            
            throw $e; // Re-throw to mark job as failed
        }
    }
    
    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Scheduled deployment job failed permanently", [
            'scheduled_deployment_id' => $this->scheduledDeployment->id,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Clear the queue job ID and mark as failed
        $this->scheduledDeployment->update([
            'status' => 'failed',
            'queue_job_id' => null,
            'last_run_at' => now()
        ]);
    }

    /**
     * Calculate the next run time based on recurrence pattern.
     */
    private function calculateNextRun($scheduledAt, $pattern)
    {
        $date = \Carbon\Carbon::parse($scheduledAt);
        
        switch ($pattern) {
            case 'daily':
                return $date->addDay();
            case 'weekly':
                return $date->addWeek();
            case 'monthly':
                return $date->addMonth();
            default:
                return null;
        }
    }
}