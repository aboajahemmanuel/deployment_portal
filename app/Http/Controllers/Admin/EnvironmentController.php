<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Environment;
use Illuminate\Http\Request;

class EnvironmentController extends Controller
{
    /**
     * Display a listing of environments.
     */
    public function index()
    {
        $environments = Environment::orderBy('name')->get();
        return view('admin.environments.index', compact('environments'));
    }

    /**
     * Show the form for creating a new environment.
     */
    public function create()
    {
        return view('admin.environments.create');
    }

    /**
     * Store a newly created environment.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:environments|regex:/^[a-z0-9-_]+$/',
            'server_base_path' => 'required|string',
            'server_unc_path' => 'required|string',
            'web_base_url' => 'required|url',
            'deploy_endpoint_base' => 'required|url',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        // Set default order if not provided
        if (!isset($validated['order'])) {
            $validated['order'] = Environment::max('order') + 1;
        }

        Environment::create($validated);

        return redirect()->route('admin.environments.index')
            ->with('success', 'Environment created successfully.');
    }

    /**
     * Display the specified environment.
     */
    public function show(Environment $environment)
    {
        $projectEnvironments = $environment->projectEnvironments()
            ->with('project')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('admin.environments.show', compact('environment', 'projectEnvironments'));
    }

    /**
     * Show the form for editing the specified environment.
     */
    public function edit(Environment $environment)
    {
        return view('admin.environments.edit', compact('environment'));
    }

    /**
     * Update the specified environment.
     */
    public function update(Request $request, Environment $environment)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|regex:/^[a-z0-9-_]+$/|unique:environments,slug,' . $environment->id,
            'server_base_path' => 'required|string',
            'server_unc_path' => 'required|string',
            'web_base_url' => 'required|url',
            'deploy_endpoint_base' => 'required|url',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ]);

        $environment->update($validated);

        return redirect()->route('admin.environments.index')
            ->with('success', 'Environment updated successfully.');
    }

    /**
     * Remove the specified environment.
     */
    public function destroy(Environment $environment)
    {
        // Check if environment has any project environments
        if ($environment->projectEnvironments()->exists()) {
            return redirect()->route('admin.environments.index')
                ->with('error', 'Cannot delete environment that has projects configured for it.');
        }
        
        // Check if environment has any deployments
        if ($environment->deployments()->exists()) {
            return redirect()->route('admin.environments.index')
                ->with('error', 'Cannot delete environment that has deployment history.');
        }

        $environment->delete();

        return redirect()->route('admin.environments.index')
            ->with('success', 'Environment deleted successfully.');
    }

    /**
     * Toggle environment active status.
     */
    public function toggleActive(Environment $environment)
    {
        $environment->update(['is_active' => !$environment->is_active]);
        
        $status = $environment->is_active ? 'activated' : 'deactivated';
        
        return redirect()->route('admin.environments.index')
            ->with('success', "Environment {$status} successfully.");
    }
}
