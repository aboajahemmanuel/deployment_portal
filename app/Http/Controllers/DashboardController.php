<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Project;
use App\Models\Deployment;
use App\Models\ScheduledDeployment;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
    }

    public function index()
    {
        $user = Auth::user();
        
        // Role-based dashboard routing
        if ($user->hasRole('admin')) {
            return $this->adminDashboard();
        } elseif ($user->hasRole('developer')) {
            return $this->developerDashboard();
        } else {
            return $this->userDashboard();
        }
    }

    /**
     * Admin Dashboard - Redirect to deployment dashboard
     */
    private function adminDashboard()
    {
        return redirect()->route('admin.deployment-dashboard');
    }

    /**
     * Developer Dashboard - Personal projects only
     */
    private function developerDashboard()
    {
        $user = Auth::user();
        
        // Get projects assigned to this developer
        $projects = Project::where('user_id', $user->id)
            ->with(['latestDeployment'])
            ->get();

        $stats = [
            'my_projects' => $projects->count(),
            'active_projects' => $projects->where('is_active', true)->count(),
            'my_deployments' => Deployment::where('user_id', $user->id)->count(),
            'recent_deployments' => Deployment::where('user_id', $user->id)
                ->with(['project'])
                ->latest()
                ->limit(5)
                ->get(),
            'my_scheduled' => ScheduledDeployment::where('user_id', $user->id)
                ->where('status', 'pending')
                ->with(['project'])
                ->orderBy('scheduled_at')
                ->limit(5)
                ->get(),
            'deployment_stats' => [
                'success' => Deployment::where('user_id', $user->id)->where('status', 'success')->count(),
                'failed' => Deployment::where('user_id', $user->id)->where('status', 'failed')->count(),
                'running' => Deployment::where('user_id', $user->id)->where('status', 'running')->count(),
            ]
        ];

        return view('dashboards.developer', compact('stats', 'projects'));
    }

    /**
     * User Dashboard - Basic view
     */
    private function userDashboard()
    {
        return view('dashboards.user');
    }
}