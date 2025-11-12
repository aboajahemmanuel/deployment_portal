<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\PipelineStage;
use App\Services\PipelineStages\SecurityScanStage;
use Illuminate\Support\Facades\Log;

class PipelineManager
{
    /**
     * Create pipeline stages for a deployment.
     */
    public function createPipelineStages(Deployment $deployment): void
    {
        $stages = $this->getDefaultStages($deployment);
        
        // Add security scanning stages
        $securityStages = SecurityScanStage::createStages($deployment);
        $stages = array_merge($stages, $securityStages);
        
        // Sort stages by order
        usort($stages, function($a, $b) {
            return $a['order'] <=> $b['order'];
        });
        
        // Create pipeline stages in database
        foreach ($stages as $stageData) {
            PipelineStage::create($stageData);
        }
        
        Log::info("Created pipeline stages for deployment {$deployment->id}", [
            'stage_count' => count($stages)
        ]);
    }
    
    /**
     * Execute the next pending stage in the pipeline.
     */
    public function executeNextStage(Deployment $deployment): bool
    {
        $nextStage = $deployment->pipelineStages()
            ->where('status', 'pending')
            ->orderBy('order')
            ->first();
            
        if (!$nextStage) {
            Log::info("No pending stages found for deployment {$deployment->id}");
            return false;
        }
        
        return $this->executeStage($deployment, $nextStage);
    }
    
    /**
     * Execute a specific pipeline stage.
     */
    public function executeStage(Deployment $deployment, PipelineStage $stage): bool
    {
        Log::info("Executing stage {$stage->name} for deployment {$deployment->id}");
        
        try {
            $success = match($stage->metadata['stage_type'] ?? $stage->name) {
                'security_scan', 'security_evaluation' => $this->executeSecurityStage($deployment, $stage),
                'preparation' => $this->executePreparationStage($deployment, $stage),
                'build' => $this->executeBuildStage($deployment, $stage),
                'deploy' => $this->executeDeployStage($deployment, $stage),
                'verify' => $this->executeVerifyStage($deployment, $stage),
                default => $this->executeGenericStage($deployment, $stage)
            };
            
            if ($success) {
                // Continue to next stage if this one succeeded
                $this->executeNextStage($deployment);
            } else {
                // Mark deployment as failed if stage fails
                $deployment->update(['status' => 'failed']);
            }
            
            return $success;
            
        } catch (\Exception $e) {
            Log::error("Stage execution failed", [
                'deployment_id' => $deployment->id,
                'stage' => $stage->name,
                'error' => $e->getMessage()
            ]);
            
            $stage->markAsFailed($e->getMessage());
            $deployment->update(['status' => 'failed']);
            
            return false;
        }
    }
    
    /**
     * Execute security scanning stage.
     */
    protected function executeSecurityStage(Deployment $deployment, PipelineStage $stage): bool
    {
        $securityStage = app(SecurityScanStage::class);
        return $securityStage->execute($deployment, $stage);
    }
    
    /**
     * Execute preparation stage.
     */
    protected function executePreparationStage(Deployment $deployment, PipelineStage $stage): bool
    {
        $stage->markAsStarted();
        
        // Simulate preparation work
        sleep(2);
        
        $output = "Preparation completed:\n";
        $output .= "- Repository: {$deployment->project->repository_url}\n";
        $output .= "- Branch: {$deployment->project->current_branch}\n";
        $output .= "- Commit: {$deployment->commit_hash}\n";
        
        $stage->markAsSuccess($output);
        return true;
    }
    
    /**
     * Execute build stage.
     */
    protected function executeBuildStage(Deployment $deployment, PipelineStage $stage): bool
    {
        $stage->markAsStarted();
        
        // Simulate build process
        sleep(3);
        
        $output = "Build completed successfully:\n";
        $output .= "- Dependencies installed\n";
        $output .= "- Assets compiled\n";
        $output .= "- Tests passed\n";
        
        $stage->markAsSuccess($output);
        return true;
    }
    
    /**
     * Execute deployment stage.
     */
    protected function executeDeployStage(Deployment $deployment, PipelineStage $stage): bool
    {
        $stage->markAsStarted();
        
        // Simulate deployment
        sleep(4);
        
        $output = "Deployment completed:\n";
        $output .= "- Files uploaded to server\n";
        $output .= "- Database migrations run\n";
        $output .= "- Services restarted\n";
        
        $stage->markAsSuccess($output);
        $deployment->update(['status' => 'success', 'completed_at' => now()]);
        return true;
    }
    
    /**
     * Execute verification stage.
     */
    protected function executeVerifyStage(Deployment $deployment, PipelineStage $stage): bool
    {
        $stage->markAsStarted();
        
        // Simulate verification
        sleep(2);
        
        $output = "Verification completed:\n";
        $output .= "- Health checks passed\n";
        $output .= "- Application responding\n";
        $output .= "- All services online\n";
        
        $stage->markAsSuccess($output);
        return true;
    }
    
    /**
     * Execute generic stage.
     */
    protected function executeGenericStage(Deployment $deployment, PipelineStage $stage): bool
    {
        $stage->markAsStarted();
        sleep(1);
        $stage->markAsSuccess("Stage completed successfully");
        return true;
    }
    
    /**
     * Get default pipeline stages for a deployment.
     */
    protected function getDefaultStages(Deployment $deployment): array
    {
        return [
            [
                'deployment_id' => $deployment->id,
                'name' => 'preparation',
                'display_name' => 'Code Preparation',
                'description' => 'Prepare source code and dependencies',
                'order' => 10,
                'status' => 'pending',
                'metadata' => ['stage_type' => 'preparation']
            ],
            [
                'deployment_id' => $deployment->id,
                'name' => 'build',
                'display_name' => 'Build & Compile',
                'description' => 'Build application and compile assets',
                'order' => 200,
                'status' => 'pending',
                'metadata' => ['stage_type' => 'build']
            ],
            [
                'deployment_id' => $deployment->id,
                'name' => 'deploy',
                'display_name' => 'Deploy Application',
                'description' => 'Deploy application to target environment',
                'order' => 300,
                'status' => 'pending',
                'metadata' => ['stage_type' => 'deploy']
            ],
            [
                'deployment_id' => $deployment->id,
                'name' => 'verify',
                'display_name' => 'Verify Deployment',
                'description' => 'Verify deployment success and run health checks',
                'order' => 400,
                'status' => 'pending',
                'metadata' => ['stage_type' => 'verify']
            ]
        ];
    }
}
