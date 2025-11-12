<?php

namespace App\Http\Controllers;

use App\Models\Deployment;
use App\Models\PipelineStage;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PipelineController extends Controller
{
    /**
     * Display the pipeline visualization for a specific deployment.
     */
    public function show(Deployment $deployment)
    {
        $this->authorize('view', $deployment->project);
        
        $deployment->load(['project', 'user', 'pipelineStages']);
        
        return view('pipelines.show', compact('deployment'));
    }

    /**
     * Display the pipeline visualization for a project's latest deployment.
     */
    public function project(Project $project)
    {
        $this->authorize('view', $project);
        
        $deployment = $project->deployments()
            ->with(['pipelineStages', 'user'])
            ->latest()
            ->first();

        if (!$deployment) {
            return redirect()->route('deployments.show', $project)
                ->with('info', 'No deployments found for this project.');
        }

        return view('pipelines.show', compact('deployment'));
    }

    /**
     * Get real-time pipeline status via AJAX.
     */
    public function status(Deployment $deployment)
    {
        $this->authorize('view', $deployment->project);
        
        $stages = $deployment->pipelineStages()->get();
        $currentStage = $deployment->currentStage();
        
        return response()->json([
            'deployment_status' => $deployment->status,
            'pipeline_progress' => $deployment->pipeline_progress,
            'current_stage' => $currentStage ? [
                'id' => $currentStage->id,
                'name' => $currentStage->name,
                'display_name' => $currentStage->display_name,
                'status' => $currentStage->status,
            ] : null,
            'stages' => $stages->map(function ($stage) {
                return [
                    'id' => $stage->id,
                    'name' => $stage->name,
                    'display_name' => $stage->display_name,
                    'status' => $stage->status,
                    'status_color' => $stage->status_color,
                    'status_icon' => $stage->status_icon,
                    'duration' => $stage->formatted_duration,
                    'started_at' => $stage->started_at?->format('H:i:s'),
                    'completed_at' => $stage->completed_at?->format('H:i:s'),
                ];
            }),
            'last_updated' => now()->toISOString(),
        ]);
    }

    /**
     * Get detailed stage information.
     */
    public function stageDetails(PipelineStage $stage)
    {
        $this->authorize('view', $stage->deployment->project);
        
        return response()->json([
            'id' => $stage->id,
            'name' => $stage->name,
            'display_name' => $stage->display_name,
            'description' => $stage->description,
            'status' => $stage->status,
            'status_color' => $stage->status_color,
            'status_icon' => $stage->status_icon,
            'started_at' => $stage->started_at?->format('Y-m-d H:i:s'),
            'completed_at' => $stage->completed_at?->format('Y-m-d H:i:s'),
            'duration' => $stage->formatted_duration,
            'output' => $stage->output,
            'error_message' => $stage->error_message,
            'metadata' => $stage->metadata,
        ]);
    }

    /**
     * Display pipeline templates.
     */
    public function templates()
    {
        $pipelineService = app(\App\Services\PipelineService::class);
        $templates = $pipelineService->getPipelineTemplates();
        
        return view('pipelines.templates', compact('templates'));
    }

    /**
     * Display pipeline analytics.
     */
    public function analytics()
    {
        $this->authorize('viewAny', Project::class);
        
        $pipelineService = app(\App\Services\PipelineService::class);
        $metrics = $pipelineService->getSystemPipelineMetrics();
        
        // Get project analytics for admins
        $projectAnalytics = [];
        if (Auth::user()->roles->contains('name', 'admin')) {
            $projects = Project::with(['deployments.pipelineStages', 'user'])->get();
            foreach ($projects as $project) {
                $analytics = $pipelineService->getProjectPipelineAnalytics($project);
                if ($analytics['total_deployments'] > 0) {
                    $projectAnalytics[] = [
                        'name' => $project->name,
                        'owner' => $project->user->name ?? 'Unassigned',
                        'total_deployments' => $analytics['total_deployments'],
                        'success_rate' => round(($analytics['successful_deployments'] / $analytics['total_deployments']) * 100),
                        'avg_duration' => $analytics['average_duration'] > 0 ? 
                            (floor($analytics['average_duration'] / 60) > 0 ? 
                                floor($analytics['average_duration'] / 60) . 'm ' : '') . 
                            ($analytics['average_duration'] % 60) . 's' : 'N/A',
                    ];
                }
            }
        }
        
        // Get stage success rates for chart
        $stageSuccessRates = [];
        $allStages = \App\Models\PipelineStage::selectRaw('name, COUNT(*) as total, SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful')
            ->groupBy('name')
            ->get();
            
        foreach ($allStages as $stage) {
            $stageSuccessRates[$stage->name] = [
                'total' => $stage->total,
                'successful' => $stage->successful,
                'rate' => $stage->total > 0 ? round(($stage->successful / $stage->total) * 100, 2) : 0,
            ];
        }
        
        return view('pipelines.analytics', compact('metrics', 'projectAnalytics', 'stageSuccessRates'));
    }

    /**
     * Create default pipeline stages for a deployment.
     */
    public function createDefaultStages(Deployment $deployment): void
    {
        $pipelineService = app(\App\Services\PipelineService::class);
        $pipelineService->createDefaultPipeline($deployment);
    }

    /**
     * Simulate pipeline execution for demonstration.
     */
    public function simulate(Deployment $deployment)
    {
        $this->authorize('deploy', $deployment->project);
        
        // Create stages if they don't exist
        if (!$deployment->hasPipelineStages()) {
            $this->createDefaultStages($deployment);
        }

        // Start the first pending stage
        $nextStage = $deployment->pipelineStages()->where('status', 'pending')->first();
        if ($nextStage) {
            $nextStage->markAsStarted();
        }

        return response()->json([
            'success' => true,
            'message' => 'Pipeline simulation started',
            'current_stage' => $nextStage?->display_name,
        ]);
    }

    /**
     * Advance pipeline to next stage (for simulation).
     */
    public function advance(Deployment $deployment)
    {
        $this->authorize('deploy', $deployment->project);
        
        $currentStage = $deployment->pipelineStages()->where('status', 'running')->first();
        
        if ($currentStage) {
            // Mark current stage as success
            $currentStage->markAsSuccess("Stage completed successfully at " . now()->format('H:i:s'));
            
            // Start next pending stage
            $nextStage = $deployment->pipelineStages()->where('status', 'pending')->first();
            if ($nextStage) {
                $nextStage->markAsStarted();
                
                return response()->json([
                    'success' => true,
                    'message' => "Advanced to {$nextStage->display_name}",
                    'current_stage' => $nextStage->display_name,
                ]);
            } else {
                // All stages completed
                $deployment->update(['status' => 'success']);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Pipeline completed successfully!',
                    'completed' => true,
                ]);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'No running stage found',
        ]);
    }

    /**
     * Fail current pipeline stage (for simulation).
     */
    public function fail(Deployment $deployment)
    {
        $this->authorize('deploy', $deployment->project);
        
        $currentStage = $deployment->pipelineStages()->where('status', 'running')->first();
        
        if ($currentStage) {
            $currentStage->markAsFailed("Stage failed during execution at " . now()->format('H:i:s'));
            $deployment->update(['status' => 'failed']);
            
            return response()->json([
                'success' => true,
                'message' => "Stage {$currentStage->display_name} failed",
                'failed_stage' => $currentStage->display_name,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No running stage found',
        ]);
    }
}
