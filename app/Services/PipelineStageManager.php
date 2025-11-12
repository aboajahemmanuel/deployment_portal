<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\PipelineStage;
use Illuminate\Support\Facades\Log;

class PipelineStageManager
{
    /**
     * Default pipeline stages configuration
     */
    protected array $defaultStages = [
        // ['name' => 'checkout', 'display_name' => 'Code Checkout', 'order' => 1],
        // ['name' => 'build', 'display_name' => 'Build', 'order' => 2],
        // ['name' => 'test', 'display_name' => 'Run Tests', 'order' => 3],
        // ['name' => 'security_scan', 'display_name' => 'Security Scan', 'order' => 4],
        // ['name' => 'deploy', 'display_name' => 'Deploy', 'order' => 5],
    ];

    /**
     * Create pipeline stages for a deployment
     */
    public function createStagesForDeployment(Deployment $deployment): void
    {
        Log::info("Creating pipeline stages for deployment {$deployment->id}");

        foreach ($this->defaultStages as $stageConfig) {
            PipelineStage::create([
                'deployment_id' => $deployment->id,
                'name' => $stageConfig['name'],
                'display_name' => $stageConfig['display_name'],
                'description' => "Automated {$stageConfig['display_name']} stage",
                'order' => $stageConfig['order'],
                'status' => 'pending',
                'metadata' => [
                    'stage_type' => $stageConfig['name'],
                    'automated' => true,
                    'created_at' => now()->toISOString()
                ]
            ]);
        }

        Log::info("Created " . count($this->defaultStages) . " pipeline stages for deployment {$deployment->id}");
    }

    /**
     * Update stage status during deployment process
     */
    public function updateStageStatus(Deployment $deployment, string $stageName, string $status, array $data = []): void
    {
        $stage = PipelineStage::where('deployment_id', $deployment->id)
            ->where('name', $stageName)
            ->first();

        if (!$stage) {
            Log::warning("Pipeline stage '{$stageName}' not found for deployment {$deployment->id}");
            return;
        }

        $updateData = ['status' => $status];

        switch ($status) {
            case 'running':
                $updateData['started_at'] = now();
                break;

            case 'success':
                $updateData['completed_at'] = now();
                if ($stage->started_at) {
                    $updateData['duration'] = now()->diffInSeconds($stage->started_at);
                }
                $updateData['output'] = $data['output'] ?? "✅ {$stage->display_name} completed successfully";
                break;

            case 'failed':
                $updateData['completed_at'] = now();
                if ($stage->started_at) {
                    $updateData['duration'] = now()->diffInSeconds($stage->started_at);
                }
                $updateData['error_message'] = $data['error'] ?? "Stage failed";
                $updateData['output'] = $data['output'] ?? "❌ {$stage->display_name} failed";
                break;

            case 'skipped':
                $updateData['completed_at'] = now();
                $updateData['error_message'] = $data['reason'] ?? 'Stage skipped';
                break;
        }

        $stage->update($updateData);
        
        Log::info("Updated pipeline stage '{$stageName}' to '{$status}' for deployment {$deployment->id}");
    }

    /**
     * Mark all remaining stages as skipped when deployment fails
     */
    public function skipRemainingStages(Deployment $deployment, string $failedStageName): void
    {
        $failedStage = PipelineStage::where('deployment_id', $deployment->id)
            ->where('name', $failedStageName)
            ->first();

        if (!$failedStage) {
            return;
        }

        // Skip all stages after the failed one
        PipelineStage::where('deployment_id', $deployment->id)
            ->where('order', '>', $failedStage->order)
            ->where('status', 'pending')
            ->update([
                'status' => 'skipped',
                'completed_at' => now(),
                'error_message' => "Skipped due to failure in {$failedStage->display_name} stage"
            ]);

        Log::info("Skipped remaining stages after '{$failedStageName}' failure for deployment {$deployment->id}");
    }

    /**
     * Simulate pipeline execution for existing deployment
     */
    public function simulateExecution(Deployment $deployment): void
    {
        $this->updateStageStatus($deployment, 'checkout', 'running');
        $this->updateStageStatus($deployment, 'checkout', 'success', ['output' => 'Code checked out successfully']);

        $this->updateStageStatus($deployment, 'build', 'running');
        $this->updateStageStatus($deployment, 'build', 'success', ['output' => 'Build completed successfully']);

        $this->updateStageStatus($deployment, 'test', 'running');
        $this->updateStageStatus($deployment, 'test', 'success', ['output' => 'All tests passed']);

        $this->updateStageStatus($deployment, 'security_scan', 'running');
        $this->updateStageStatus($deployment, 'security_scan', 'success', ['output' => 'Security scan completed']);

        $this->updateStageStatus($deployment, 'deploy', 'running');
        
        if ($deployment->status === 'success') {
            $this->updateStageStatus($deployment, 'deploy', 'success', ['output' => 'Deployment successful']);
        } else {
            $this->updateStageStatus($deployment, 'deploy', 'failed', ['error' => 'Deployment failed']);
            $this->skipRemainingStages($deployment, 'deploy');
        }
    }
}
