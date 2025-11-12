<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Deployment;
use App\Models\User;

class DeploymentAdminController extends Controller
{
    /**
     * Display the deployment manager dashboard.
     */
    public function dashboard()
    {
        // Get basic stats
        $stats = [
            'total_projects' => Project::count(),
            'active_projects' => Project::where('is_active', true)->count(),
            'total_deployments' => Deployment::count(),
            'recent_deployments' => Deployment::with(['project', 'user'])
                ->latest()
                ->limit(10)
                ->get(),
            'deployment_stats' => [
                'success' => Deployment::where('status', 'success')->count(),
                'failed' => Deployment::where('status', 'failed')->count(),
                'running' => Deployment::where('status', 'running')->count(),
            ]
        ];

        // Get pipeline analytics data
        $pipelineService = app(\App\Services\PipelineService::class);
        $pipelineMetrics = $pipelineService->getSystemPipelineMetrics();
        
        // Get project analytics
        $projectAnalytics = [];
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

        return view('admin.deployment-dashboard', compact('stats', 'pipelineMetrics', 'projectAnalytics', 'stageSuccessRates'));
    }

    /**
     * Display a listing of all projects.
     */
    public function projects()
    {
        $projects = Project::with('latestDeployment')->get();
        return view('deployments.index', compact('projects'));
    }

    /**
     * Display a listing of all deployments.
     */
    public function deployments()
    {
        $deployments = Deployment::with(['project', 'user'])->latest()->paginate(20);
        return view('admin.deployments.index', compact('deployments'));
    }

    /**
     * Display a listing of all users.
     */
    public function users()
    {
        $users = User::with('roles')->latest()->paginate(20);
        return view('admin.users.index', compact('users'));
    }
}