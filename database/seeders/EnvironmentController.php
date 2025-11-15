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
        $this->authorize('viewAny', Environment::class);
        
        $environments = Environment::orderBy('order')->orderBy('name')->get();
        return view('admin.environments.index', compact('environments'));
    }

    /**
     * Show the form for creating a new environment.
     */
    public function create()
    {
        $this->authorize('create', Environment::class);
        
        return view('admin.environments.create');
    }

    /**
     * Store a newly created environment.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Environment::class);
        
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
        $this->authorize('view', $environment);
        
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
        return view('environments.edit', compact('environment'));
    }

    /**
     * Update the specified environment.
     */
    public function update(Request $request, Environment $environment)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:environments,slug,' . $environment->id,
            'server_base_path' => 'required|string',
            'server_unc_path' => 'required|string',
            'web_base_url' => 'required|url',
            'deploy_endpoint_base' => 'required|url',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'integer',
        ]);

        $environment->update($validated);

        return redirect()->route('environments.index')
            ->with('success', 'Environment updated successfully.');
    }

    /**
     * Remove the specified environment.
     */
    public function destroy(Environment $environment)
    {
        $environment->delete();

        return redirect()->route('environments.index')
            ->with('success', 'Environment deleted successfully.');
    }
}
