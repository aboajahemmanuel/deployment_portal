<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ScheduledDeployment;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;

class ScheduledDeploymentController extends Controller
{
    /**
     * Display a listing of scheduled deployments.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Filter scheduled deployments based on user role
        if ($user->roles->contains('name', 'admin')) {
            // Admins see all scheduled deployments
            $scheduledDeployments = ScheduledDeployment::with(['project', 'user'])
                ->orderBy('scheduled_at', 'asc')
                ->paginate(20);
        } elseif ($user->roles->contains('name', 'developer')) {
            // Developers only see their own scheduled deployments
            $scheduledDeployments = ScheduledDeployment::where('user_id', $user->id)
                ->with(['project', 'user'])
                ->orderBy('scheduled_at', 'asc')
                ->paginate(20);
        } else {
            // Regular users see no scheduled deployments
            $scheduledDeployments = ScheduledDeployment::where('id', null)->paginate(20);
        }

        return view('deployments.scheduled.index', compact('scheduledDeployments'));
    }

    /**
     * Show the form for creating a new scheduled deployment.
     */
    public function create()
    {
        $user = Auth::user();
        
        // Filter projects based on user role
        if ($user->roles->contains('name', 'admin')) {
            // Admins can schedule deployments for all projects
            $projects = Project::all();
        } elseif ($user->roles->contains('name', 'developer')) {
            // Developers can only schedule deployments for their own projects
            $projects = Project::where('user_id', $user->id)->get();
        } else {
            // Regular users cannot create scheduled deployments
            $projects = collect();
        }
        
        return view('deployments.scheduled.create', compact('projects'));
    }

    /**
     * Store a newly created scheduled deployment in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', ScheduledDeployment::class);
        
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'scheduled_at' => 'required|date|after:now',
            'description' => 'nullable|string|max:500',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|required_if:is_recurring,1|in:daily,weekly,monthly',
        ]);

        // Ensure developers can only schedule deployments for their own projects
        $project = Project::findOrFail($validated['project_id']);
        $this->authorize('deploy', $project);

        // Convert the scheduled_at to the application timezone
        $scheduledAt = Date::parse($validated['scheduled_at']);

        $scheduledDeployment = ScheduledDeployment::create([
            'project_id' => $validated['project_id'],
            'user_id' => Auth::id(),
            'scheduled_at' => $scheduledAt,
            'description' => $validated['description'] ?? null,
            'is_recurring' => $validated['is_recurring'] ?? false,
            'recurrence_pattern' => ($validated['is_recurring'] ?? false) ? ($validated['recurrence_pattern'] ?? null) : null,
            'next_run_at' => ($validated['is_recurring'] ?? false) ? $this->calculateNextRun($scheduledAt, $validated['recurrence_pattern'] ?? null) : null,
        ]);

        return redirect()->route('scheduled-deployments.index')
            ->with('success', 'Scheduled deployment created successfully.');
    }

    /**
     * Display the specified scheduled deployment.
     */
    public function show(ScheduledDeployment $scheduledDeployment)
    {
        $this->authorize('view', $scheduledDeployment);
        
        $scheduledDeployment->load(['project', 'user']);
        return view('deployments.scheduled.show', compact('scheduledDeployment'));
    }

    /**
     * Show the form for editing the specified scheduled deployment.
     */
    public function edit(ScheduledDeployment $scheduledDeployment)
    {
        $this->authorize('update', $scheduledDeployment);
        
        $user = Auth::user();
        
        // Filter projects based on user role
        if ($user->roles->contains('name', 'admin')) {
            $projects = Project::all();
        } elseif ($user->roles->contains('name', 'developer')) {
            $projects = Project::where('user_id', $user->id)->get();
        } else {
            $projects = collect();
        }
        
        return view('deployments.scheduled.edit', compact('scheduledDeployment', 'projects'));
    }

    /**
     * Update the specified scheduled deployment in storage.
     */
    public function update(Request $request, ScheduledDeployment $scheduledDeployment)
    {
        $this->authorize('update', $scheduledDeployment);
        
        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'scheduled_at' => 'required|date|after:now',
            'description' => 'nullable|string|max:500',
            'is_recurring' => 'boolean',
            'recurrence_pattern' => 'nullable|required_if:is_recurring,1|in:daily,weekly,monthly',
        ]);

        // Ensure developers can only update to their own projects
        $project = Project::findOrFail($validated['project_id']);
        $this->authorize('deploy', $project);

        // Convert the scheduled_at to the application timezone
        $scheduledAt = Date::parse($validated['scheduled_at']);

        $scheduledDeployment->update([
            'project_id' => $validated['project_id'],
            'scheduled_at' => $scheduledAt,
            'description' => $validated['description'] ?? null,
            'is_recurring' => $validated['is_recurring'] ?? false,
            'recurrence_pattern' => ($validated['is_recurring'] ?? false) ? ($validated['recurrence_pattern'] ?? null) : null,
            'next_run_at' => ($validated['is_recurring'] ?? false) ? $this->calculateNextRun($scheduledAt, $validated['recurrence_pattern'] ?? null) : null,
        ]);

        return redirect()->route('scheduled-deployments.index')
            ->with('success', 'Scheduled deployment updated successfully.');
    }

    /**
     * Remove the specified scheduled deployment from storage.
     */
    public function destroy(ScheduledDeployment $scheduledDeployment)
    {
        $this->authorize('delete', $scheduledDeployment);
        
        $scheduledDeployment->delete();

        return redirect()->route('scheduled-deployments.index')
            ->with('success', 'Scheduled deployment cancelled successfully.');
    }

    /**
     * Cancel a scheduled deployment.
     */
    public function cancel(ScheduledDeployment $scheduledDeployment)
    {
        $this->authorize('update', $scheduledDeployment);
        
        $scheduledDeployment->update(['status' => 'cancelled']);

        return redirect()->route('scheduled-deployments.index')
            ->with('success', 'Scheduled deployment cancelled successfully.');
    }

    /**
     * Calculate the next run time based on recurrence pattern.
     */
    private function calculateNextRun($scheduledAt, $pattern)
    {
        if (!$pattern) {
            return null;
        }
        
        $date = Date::parse($scheduledAt);
        
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