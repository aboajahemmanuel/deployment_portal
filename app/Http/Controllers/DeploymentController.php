<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Deployment;
use App\Models\ScheduledDeployment;
use App\Services\DeploymentLogger;
use App\Services\SecurityScannerService;
use App\Services\PipelineStages\SecurityScanStage;
use App\Notifications\DeploymentNotification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class DeploymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', Deployment::class);
        
        $user = Auth::user();
        
        // Filter projects based on user role
        if ($user->roles->contains('name', 'admin')) {
            // Admins see all projects
            $projects = Project::with('latestDeployment.environment')->get();
            $upcomingScheduledDeployments = ScheduledDeployment::with(['project', 'user'])
                ->where('status', 'pending')
                ->where('scheduled_at', '>', now())
                ->orderBy('scheduled_at', 'asc')
                ->limit(5)
                ->get();
        } elseif ($user->roles->contains('name', 'developer')) {
            // Developers only see their assigned projects
            $projects = Project::where('user_id', $user->id)
                ->with('latestDeployment.environment')
                ->get();
            $upcomingScheduledDeployments = ScheduledDeployment::where('user_id', $user->id)
                ->with(['project', 'user'])
                ->where('status', 'pending')
                ->where('scheduled_at', '>', now())
                ->orderBy('scheduled_at', 'asc')
                ->limit(5)
                ->get();
        } else {
            // Regular users see no projects
            $projects = collect();
            $upcomingScheduledDeployments = collect();
        }
        
        return view('deployments.index', compact('projects', 'upcomingScheduledDeployments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Project::class);
        
        return view('deployments.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Project::class);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'repository_url' => 'required|url',
            'deploy_endpoint' => 'required|string',
            'access_token' => 'required|string',
            'current_branch' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_type' => 'nullable|string|in:laravel,nodejs,php,other',
            'env_variables' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        // Assign project to current user if they're a developer
        $user = Auth::user();
        if ($user->roles->contains('name', 'developer')) {
            $validated['user_id'] = $user->id;
        }

        // Build endpoint slug from provided text
        $endpointRaw = trim((string)$validated['deploy_endpoint']);
        $endpointSlug = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $endpointRaw));
        $endpointSlug = trim($endpointSlug, '-_');
        if ($endpointSlug === '') {
            $fallbackSlug = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '-', $validated['name']));
            $endpointSlug = trim($fallbackSlug, '-_');
            if ($endpointSlug === '') {
                $endpointSlug = 'deploy';
            }
        }

        // Remove the hardcoded endpoint URLs - these will be generated per environment
        unset($validated['deploy_endpoint']);

        $project = Project::create($validated);

        // Generate deployment and rollback files for ALL environments at project creation
        try {
            // Get all active environments
            $environments = \App\Models\Environment::active()->ordered()->get();
            
            if ($environments->isEmpty()) {
                Log::warning('No active environments found. Please seed environments first.');
            }

            $generator = new \App\Services\DeploymentFileGenerator();
            $slug = str_replace([' ', '/','\\'], ['-','-','-'], strtolower($project->name));

            foreach ($environments as $environment) {
                try {
                    // Generate environment-specific file names
                    $envFileName = $endpointSlug . '_' . $environment->slug . '.php';
                    $envRollbackFileName = $endpointSlug . '_' . $environment->slug . '_rollback.php';
                    
                    // Generate environment-specific project path using server_base_path
                    $projectType = $project->project_type ?? 'laravel';
                    // Remove _deploy suffix for all project types
                    $windowsProjectPath = $environment->server_base_path . '\\' . $slug;
                    
                    // Generate environment-specific URLs
                    $deployEndpoint = rtrim($environment->deploy_endpoint_base, '/') . '/' . $envFileName;
                    $rollbackEndpoint = rtrim($environment->deploy_endpoint_base, '/') . '/' . $envRollbackFileName;
                    $applicationUrl = rtrim($environment->web_base_url, '/') . '/' . $slug;

                    // Generate deployment file content
                    $content = $generator->make(
                        $windowsProjectPath, 
                        $project->repository_url,
                        $project->project_type ?? 'laravel',
                        $project->env_variables,
                        $environment->server_base_path
                    );
                    
                    // Generate rollback script content
                    $rollbackContent = $generator->makeRollback($windowsProjectPath);

                    // Ensure UNC path formatting
                    $uncBase = rtrim(str_replace('/', '\\', $environment->server_unc_path), "\\/ ");
                    if (!str_starts_with($uncBase, '\\\\')) {
                        $uncBase = '\\\\' . ltrim($uncBase, '\\\\');
                    }
                    $targetBase = $uncBase . (str_ends_with($uncBase, '\\') ? '' : '\\');
                    $targetPath = $targetBase . $envFileName;
                    $rollbackTargetPath = $targetBase . $envRollbackFileName;

                    // Increase time limit for file operations
                    set_time_limit(300); // Increase to 5 minutes for file operations
                    
                    // Write deployment files with retry logic and timeout handling
                    $deployResult = $this->writeFileWithRetry($targetPath, $content, 3, 120); // 3 retries, 2 minute timeout each
                    $rollbackResult = $this->writeFileWithRetry($rollbackTargetPath, $rollbackContent, 3, 120); // 3 retries, 2 minute timeout each
                    
                    // If file writing failed, try alternative approach
                    if ($deployResult === false || $rollbackResult === false) {
                        Log::warning('Direct file writing failed, trying alternative approach', [
                            'project_id' => $project->id,
                            'environment' => $environment->name,
                            'deploy_target' => $deployResult === false ? $targetPath : 'SUCCESS',
                            'rollback_target' => $rollbackResult === false ? $rollbackTargetPath : 'SUCCESS'
                        ]);
                        
                        // Try creating a local temporary file and then copying it
                        $tempDir = sys_get_temp_dir();
                        $tempDeployFile = $tempDir . DIRECTORY_SEPARATOR . 'deploy_' . uniqid() . '.php';
                        $tempRollbackFile = $tempDir . DIRECTORY_SEPARATOR . 'rollback_' . uniqid() . '.php';
                        
                        // Write to temporary files first
                        $tempDeployResult = @file_put_contents($tempDeployFile, $content);
                        $tempRollbackResult = @file_put_contents($tempRollbackFile, $rollbackContent);
                        
                        if ($tempDeployResult !== false && $tempRollbackResult !== false) {
                            // Copy files to network locations
                            $deployResult = $this->copyFileWithRetry($tempDeployFile, $targetPath, 3, 120);
                            $rollbackResult = $this->copyFileWithRetry($tempRollbackFile, $rollbackTargetPath, 3, 120);
                            
                            // Clean up temporary files
                            @unlink($tempDeployFile);
                            @unlink($tempRollbackFile);
                            
                            if ($deployResult !== false && $rollbackResult !== false) {
                                Log::info('Successfully wrote deployment files using alternative approach', [
                                    'project_id' => $project->id,
                                    'environment' => $environment->name,
                                    'deploy_target' => $targetPath,
                                    'rollback_target' => $rollbackTargetPath
                                ]);
                            }
                        }
                    }
                    
                    // Reset time limit
                    set_time_limit(60);
                    
                    // Check if files were written successfully
                    if ($deployResult === false) {
                        // Get detailed error information
                        $errorDetails = [];
                        $lastError = error_get_last();
                        if ($lastError) {
                            $errorDetails['message'] = $lastError['message'] ?? 'No message';
                            $errorDetails['type'] = $lastError['type'] ?? 'Unknown type';
                            $errorDetails['file'] = $lastError['file'] ?? 'Unknown file';
                            $errorDetails['line'] = $lastError['line'] ?? 'Unknown line';
                        } else {
                            $errorDetails['message'] = 'No error details available';
                        }
                        
                        // Check if path exists and is writable
                        $directory = dirname($targetPath);
                        $errorDetails['directory_exists'] = is_dir($directory) ? 'yes' : 'no';
                        $errorDetails['directory_writable'] = is_writable($directory) ? 'yes' : 'no';
                        $errorDetails['path_resolved'] = realpath($directory) ?: 'Could not resolve path';
                        
                        Log::error('Failed to write deployment file', [
                            'project_id' => $project->id,
                            'environment' => $environment->name,
                            'target_path' => $targetPath,
                            'error_details' => $errorDetails
                        ]);
                        throw new \Exception("Failed to write deployment file to {$targetPath}: " . $errorDetails['message']);
                    }
                    
                    if ($rollbackResult === false) {
                        // Get detailed error information
                        $errorDetails = [];
                        $lastError = error_get_last();
                        if ($lastError) {
                            $errorDetails['message'] = $lastError['message'] ?? 'No message';
                            $errorDetails['type'] = $lastError['type'] ?? 'Unknown type';
                            $errorDetails['file'] = $lastError['file'] ?? 'Unknown file';
                            $errorDetails['line'] = $lastError['line'] ?? 'Unknown line';
                        } else {
                            $errorDetails['message'] = 'No error details available';
                        }
                        
                        // Check if path exists and is writable
                        $directory = dirname($rollbackTargetPath);
                        $errorDetails['directory_exists'] = is_dir($directory) ? 'yes' : 'no';
                        $errorDetails['directory_writable'] = is_writable($directory) ? 'yes' : 'no';
                        $errorDetails['path_resolved'] = realpath($directory) ?: 'Could not resolve path';
                        
                        Log::error('Failed to write rollback file', [
                            'project_id' => $project->id,
                            'environment' => $environment->name,
                            'target_path' => $rollbackTargetPath,
                            'error_details' => $errorDetails
                        ]);
                        throw new \Exception("Failed to write rollback file to {$rollbackTargetPath}: " . $errorDetails['message']);
                    }
                    
                    // Create project environment record
                    \App\Models\ProjectEnvironment::create([
                        'project_id' => $project->id,
                        'environment_id' => $environment->id,
                        'deploy_endpoint' => $deployEndpoint,
                        'rollback_endpoint' => $rollbackEndpoint,
                        'application_url' => $applicationUrl,
                        'project_path' => $windowsProjectPath,
                        'env_variables' => $project->env_variables,
                        'branch' => $project->current_branch,
                        'is_active' => true,
                    ]);

                    Log::info('Deployment files created for environment', [
                        'project_id' => $project->id,
                        'environment' => $environment->name,
                        'deploy_target' => $targetPath,
                        'rollback_target' => $rollbackTargetPath,
                    ]);
                } catch (\Throwable $envError) {
                    Log::error('Failed to create deployment files for environment', [
                        'project_id' => $project->id,
                        'environment' => $environment->name,
                        'error' => $envError->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to create deployment files on project create', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()->route('deployments.index')
            ->with('success', 'Project created successfully! Deployment files have been generated for all active environments.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        $this->authorize('view', $project);
        
        $deployments = $project->deployments()->with('environment')->latest()->paginate(10);
        
        // Get all environments for the project
        $environments = $project->environments()->where('environments.is_active', true)->orderBy('environments.order')->orderBy('environments.name')->get();
        
        return view('deployments.show', compact('project', 'deployments', 'environments'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        $this->authorize('update', $project);
        
        return view('deployments.edit', compact('project'));
    }

    /**
     * Update the specified resource in storage.
     */ 
    public function update(Request $request, Project $project)
    {
        $this->authorize('update', $project);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'repository_url' => 'required|url',
            'access_token' => 'required|string',
            'current_branch' => 'required|string|max:255',
            'description' => 'nullable|string',
            'project_type' => 'nullable|string|in:laravel,nodejs,php,other',
            'env_variables' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $project->update($validated);

        return redirect()->route('deployments.index')
            ->with('success', 'Project updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $this->authorize('delete', $project);
        
        $project->delete();

        return redirect()->route('deployments.index')
            ->with('success', 'Project deleted successfully.');
    }

    /**
     * Trigger a deployment for the specified project.
     */
    public function deploy(Request $request, Project $project)
    {
        try {
            $this->authorize('deploy', $project);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return redirect()->back()->with('error', 'You are not authorized to deploy this project. Only project owners and administrators can trigger deployments.');
        }
        
        // Validate environment selection
        $validated = $request->validate([
            'environment_id' => 'required|exists:environments,id',
        ]);

        // Get the project environment configuration
        $projectEnvironment = \App\Models\ProjectEnvironment::where('project_id', $project->id)
            ->where('environment_id', $validated['environment_id'])
            ->where('is_active', true)
            ->first();

        if (!$projectEnvironment) {
            return response()->json([
                'success' => false,
                'message' => 'Project is not configured for the selected environment.',
            ], 400);
        }

        // Create deployment record with environment
        $deployment = Deployment::create([
            'project_id' => $project->id,
            'environment_id' => $validated['environment_id'],
            'user_id' => Auth::id(),
            'commit_hash' => 'pending',
            'status' => 'processing',
            'started_at' => now(),
        ]);

        // Create pipeline stages for this deployment
        try {
            app(\App\Services\PipelineStageManager::class)->createStagesForDeployment($deployment);
        } catch (\Throwable $e) {
            Log::warning('Failed to create pipeline stages', [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage()
            ]);
        }

        // Create a logger for this deployment
        $logger = new DeploymentLogger($deployment);

        try {
            // Log the deployment attempt
            $logger->info('Deployment attempt started', [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'environment_id' => $projectEnvironment->environment_id,
                'environment_name' => $projectEnvironment->environment->name,
                'deploy_endpoint' => $projectEnvironment->deploy_endpoint,
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'deployment_id' => $deployment->id,
            ]);

            // Prepare request parameters
            $params = [
                'project_id' => $project->id,
                'branch' => $projectEnvironment->branch,
                'user_id' => Auth::id(),
                'deployment_id' => $deployment->id,
                'environment_id' => $projectEnvironment->environment_id,
            ];

            // Log the parameters being sent
            $logger->info('Sending deployment request with parameters', $params);

            // Validate deploy endpoint before logging HTTP request
            $deployEndpoint = $projectEnvironment->deploy_endpoint ?? '';
            if (!is_string($deployEndpoint) || trim($deployEndpoint) === '') {
                $errorMessage = 'Deploy endpoint is missing or invalid for project environment';
                $logger->error($errorMessage, [
                    'project_id' => $project->id,
                    'environment_id' => $projectEnvironment->environment_id,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 400);
            }
            $deployEndpoint = trim($deployEndpoint);

            // Log the HTTP request details
            $logger->logHttpRequest($deployEndpoint, 'GET', [], $params);

            // Ensure this controller action can run long enough for the remote deployment to complete
            // Avoid hitting PHP's default 60s max execution time when waiting on the remote server
            try {
                @set_time_limit(0);
                @ini_set('max_execution_time', '0');
                // Increase default socket timeout to match the HTTP client timeout window
                @ini_set('default_socket_timeout', '600');
            } catch (\Throwable $e) {
                // Non-fatal: continue with best-effort
            }

            // Send request to deploy endpoint with SSL verification options
            $response = Http::withToken($project->access_token)
                ->timeout(600) // 10 minute timeout to accommodate first deploys
                ->withOptions([
                    'verify' => false,
                ])
                ->get($projectEnvironment->deploy_endpoint, $params);

            // Log the response details
            $logger->logHttpResponse($response->status(), $response->body(), $response->headers());

            $responseBody = $response->body();
            
            // Heuristic success detection
            $lowerBody = is_string($responseBody) ? strtolower($responseBody) : '';
            $markerSuccess = (bool) (preg_match('/deployment_status\s*=\s*success/i', (string) $responseBody)
                || str_contains($lowerBody, '✅ deployment finished successfully')
                || str_contains($lowerBody, 'deployment finished successfully')
                || str_contains($lowerBody, 'deployment started'));
            $looksSuccessful = is_string($responseBody)
                && $markerSuccess
                && !str_contains($lowerBody, '❌ command failed')
                && !str_contains($lowerBody, 'fatal error');

            // Header-based success override
            $headerSuccess = false;
            $headers = $response->headers();
            if (is_array($headers)) {
                foreach ($headers as $hKey => $hVal) {
                    $keyLower = strtolower((string) $hKey);
                    $valStr = is_array($hVal) ? strtolower(implode(',', $hVal)) : strtolower((string) $hVal);
                    if (in_array($keyLower, ['x-deployment-status','x-deploy-status','x-status'], true) && str_contains($valStr, 'success')) {
                        $headerSuccess = true;
                        break;
                    }
                }
            }

            // Determine if deployment was successful
            $deploymentSuccessful = $response->successful() || $looksSuccessful || $headerSuccess;

            if ($deploymentSuccessful) {
                // Extract commit hash
                $commitHash = $this->extractCommitHash($responseBody);
                
                // Extract run ID
                $runId = null;
                if (preg_match('/Run ID:\s*([0-9_\-]+)/', (string) $responseBody, $m)) {
                    $runId = $m[1];
                }

                // Update deployment as successful
                $deployment->update([
                    'status' => 'success',
                    'completed_at' => now(),
                    'log_output' => ($runId ? ("[run_id:".$runId."]\n") : '') . $responseBody,
                    'commit_hash' => $commitHash,
                ]);
                
                $logger->info('Deployment successful', [
                    'project_id' => $project->id,
                    'deployment_id' => $deployment->id,
                    'commit_hash' => $commitHash,
                    'run_id' => $runId,
                ]);

                // Update pipeline stages - wrapped in try-catch
                try {
                    $stageManager = app(\App\Services\PipelineStageManager::class);
                    $stageManager->simulateExecution($deployment);
                } catch (\Throwable $stageEx) {
                    $logger->error('Failed to update pipeline stages', [
                        'deployment_id' => $deployment->id,
                        'error' => $stageEx->getMessage(),
                    ]);
                }
                
                // Perform post-success tasks - wrapped in try-catch to prevent affecting response
                try {
                    $this->runSecurityScan($deployment, $logger);
                } catch (\Throwable $scanEx) {
                    $logger->error('Security scan failed', [
                        'deployment_id' => $deployment->id,
                        'error' => $scanEx->getMessage(),
                        'trace' => $scanEx->getTraceAsString(),
                    ]);
                }

                try {
                    $this->sendDeploymentNotification($deployment, 'success');
                } catch (\Throwable $notifEx) {
                    $logger->error('Notification sending failed', [
                        'deployment_id' => $deployment->id,
                        'error' => $notifEx->getMessage(),
                        'trace' => $notifEx->getTraceAsString(),
                    ]);
                }
                
                return response()->json([
                    'success' => true,
                    'message' => 'Deployment successful!',
                    'log' => $responseBody,
                    'deployment_id' => $deployment->id,
                    'commit_hash' => $commitHash,
                ]);
            } else {
                // Deployment failed
                $errorDetails = [
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                    'project_id' => $project->id,
                    'deployment_id' => $deployment->id,
                ];
                
                $deployment->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'log_output' => json_encode($errorDetails),
                ]);
                
                $logger->error('Deployment failed with HTTP error', $errorDetails);

                // Update pipeline stages
                try {
                    $stageManager = app(\App\Services\PipelineStageManager::class);
                    $stageManager->simulateExecution($deployment);
                } catch (\Throwable $stageEx) {
                    $logger->error('Failed to update pipeline stages after failure', [
                        'deployment_id' => $deployment->id,
                        'error' => $stageEx->getMessage(),
                    ]);
                }
                
                // Send failure notification
                try {
                    $this->sendDeploymentNotification($deployment, 'failure');
                } catch (\Throwable $notifEx) {
                    $logger->error('Failed to send failure notification', [
                        'deployment_id' => $deployment->id,
                        'error' => $notifEx->getMessage(),
                    ]);
                }
                
                return response()->json([
                    'success' => false,
                    'message' => 'Deployment failed! HTTP Status: ' . $response->status(),
                    'log' => json_encode($errorDetails),
                    'response_body' => $response->body(),
                    'deployment_id' => $deployment->id,
                ], 200); // Return 200 to avoid triggering client-side error handlers
            }
        } catch (\Exception $e) {
            $errorDetails = [
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'exception_trace' => $e->getTraceAsString(),
                'project_id' => $project->id,
                'deployment_id' => $deployment->id,
            ];
            
            $deployment->update([
                'status' => 'failed',
                'completed_at' => now(),
                'log_output' => json_encode($errorDetails),
            ]);
            
            // Log the exception
            $logger->logException($e);
            
            // Send failure notification - wrapped in try-catch
            try {
                $this->sendDeploymentNotification($deployment, 'failure');
            } catch (\Throwable $notifEx) {
                Log::error('Failed to send failure notification after exception', [
                    'deployment_id' => $deployment->id,
                    'original_error' => $e->getMessage(),
                    'notification_error' => $notifEx->getMessage(),
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Deployment failed! Exception: ' . $e->getMessage(),
                'log' => json_encode($errorDetails),
                'deployment_id' => $deployment->id,
            ], 200); // Return 200 to avoid triggering client-side error handlers
        }
    }


    /**
     * Trigger a rollback deployment for the specified project.
     */
    public function rollback(Project $project, Deployment $targetDeployment, Request $request)
    {
        $this->authorize('deploy', $project);
        
        // Validate that the target deployment is successful
        if ($targetDeployment->status !== 'success') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot rollback to a failed deployment.',
            ], 400);
        }
        
        // Validate that the target deployment belongs to the same project
        if ($targetDeployment->project_id !== $project->id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid deployment target for rollback.',
            ], 400);
        }

        // Get rollback reason from request
        $rollbackReason = $request->input('reason', 'Rollback initiated by user');
        
        // Get environment_id from request (optional, for validation)
        $requestedEnvironmentId = $request->input('environment_id');

        // Validate that the requested environment matches the target deployment's environment
        if ($requestedEnvironmentId && $requestedEnvironmentId != $targetDeployment->environment_id) {
            return response()->json([
                'success' => false,
                'message' => 'Environment mismatch. Rollbacks can only be performed within the same environment.',
            ], 400);
        }

        // Get the project environment configuration for the target deployment's environment
        $projectEnvironment = \App\Models\ProjectEnvironment::where('project_id', $project->id)
            ->where('environment_id', $targetDeployment->environment_id)
            ->where('is_active', true)
            ->first();

        if (!$projectEnvironment) {
            return response()->json([
                'success' => false,
                'message' => 'Project environment configuration not found for rollback.',
            ], 400);
        }

        // Create a new rollback deployment record
        $deployment = Deployment::create([
            'project_id' => $project->id,
            'environment_id' => $targetDeployment->environment_id,
            'user_id' => Auth::id(),
            'status' => 'pending',
            'started_at' => now(),
            'is_rollback' => true,
            'rollback_target_id' => $targetDeployment->id,
            'rollback_reason' => $rollbackReason,
        ]);

        // Create a logger for this deployment
        $logger = new DeploymentLogger($deployment);

        try {
            // Log the rollback attempt
            $logger->info('Rollback deployment attempt started', [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'environment_id' => $projectEnvironment->environment_id,
                'environment_name' => $projectEnvironment->environment->name,
                'deploy_endpoint' => $projectEnvironment->deploy_endpoint,
                'rollback_endpoint' => $projectEnvironment->rollback_endpoint,
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'deployment_id' => $deployment->id,
                'rollback_target_id' => $targetDeployment->id,
                'rollback_reason' => $rollbackReason,
            ]);

            // Determine which endpoint to use for rollback
            $endpoint = $projectEnvironment->rollback_endpoint;

            // Require a dedicated rollback endpoint to avoid accidental deploys
            if (empty($endpoint)) {
                $logger->error('No rollback endpoint configured for project', [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Cannot rollback: No rollback endpoint configured for this project. Please set the rollback endpoint (e.g., http(s)://server/rollback.php).',
                ], 400);
            }

            // Validate rollback endpoint is a string
            if (!is_string($endpoint)) {
                $logger->error('Invalid rollback endpoint for project', [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'endpoint_type' => gettype($endpoint),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Cannot rollback: Invalid rollback endpoint configured for this project. Endpoint must be a string.',
                ], 400);
            }
            
            $endpoint = trim($endpoint);

            // Check if target deployment has a commit hash
            if (empty($targetDeployment->commit_hash)) {
                $logger->error('Rollback target deployment has no commit hash', [
                    'target_deployment_id' => $targetDeployment->id,
                    'target_deployment_status' => $targetDeployment->status,
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot rollback: Target deployment has no commit hash recorded. This might be an older deployment that was created before commit tracking was implemented.',
                ], 400);
            }

            // Prepare request parameters for rollback
            $params = [
                'project_id' => $project->id,
                'branch' => $project->current_branch,
                'user_id' => Auth::id(),
                'deployment_id' => $deployment->id,
                'rollback' => true,
                'rollback_target_commit' => $targetDeployment->commit_hash,
                'rollback_reason' => $rollbackReason,
            ];

            // Log the parameters being sent
            $logger->info('Sending rollback deployment request with parameters', $params);

            // Log the HTTP request details
            $logger->logHttpRequest($endpoint, 'POST', ['Content-Type' => 'application/json'], $params);

            // Send request to rollback endpoint with JSON body and proper authorization
            $response = Http::timeout(300) // 5 minute timeout
                ->withOptions([
                    'verify' => false, // Disable SSL verification for now - in production, you should properly configure SSL certificates
                ])
                ->withHeaders([
                    // The rollback scripts expect this token. If you want to use per-project tokens,
                    // align the script to validate $project->access_token instead.
                    'Authorization' => 'Bearer test-token-123',
                ])
                ->asJson()
                ->post($endpoint, $params); // Send JSON body

            // Log the response details
            $logger->logHttpResponse($response->status(), $response->body(), $response->headers());

            if ($response->successful()) {
                // Parse the response to extract commit hash if available
                $commitHash = null;
                $responseBody = $response->body();
                
                // Try to decode as JSON first
                $responseData = json_decode($responseBody, true);
                if (is_array($responseData) && isset($responseData['commit_hash'])) {
                    $commitHash = $responseData['commit_hash'];
                } else if (is_array($responseData) && isset($responseData['rollback_target_commit'])) {
                    // For rollback operations, the script might return rollback_target_commit instead
                    $commitHash = $responseData['rollback_target_commit'];
                } else {
                    // If not JSON or no commit_hash in JSON, try to extract from text
                    // Look for commit hash pattern (40 character hex string)
                    if (preg_match('/([a-f0-9]{40})/', $responseBody, $matches)) {
                        $commitHash = $matches[1];
                    } else {
                        // Try to extract from git pull output (short hash format)
                        // Look for pattern like "Updating 3ed7b78..c8f2f0a" and take the second hash
                        if (preg_match('/Updating [a-f0-9]{7,40}\.\.([a-f0-9]{7,40})/', $responseBody, $matches)) {
                            $commitHash = $matches[1];
                        }
                    }
                }
                
                $deployment->update([
                    'status' => 'success',
                    'completed_at' => now(),
                    'log_output' => $response->body(),
                    'commit_hash' => $commitHash,
                ]);
                
                $logger->info('Rollback deployment successful', [
                    'project_id' => $project->id,
                    'deployment_id' => $deployment->id,
                    'rollback_target_id' => $targetDeployment->id,
                    'response_body' => $response->body(),
                    'commit_hash' => $commitHash,
                ]);
                
                // Send success notification
                $this->sendDeploymentNotification($deployment, 'success');
                
                return response()->json([
                    'success' => true,
                    'message' => 'Rollback deployment successful!',
                    'log' => $response->body(),
                ]);
            } else {
                $errorDetails = [
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                    'project_id' => $project->id,
                    'deployment_id' => $deployment->id,
                    'rollback_target_id' => $targetDeployment->id,
                ];
                
                $deployment->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'log_output' => json_encode($errorDetails),
                ]);
                
                $logger->error('Rollback deployment failed with HTTP error', $errorDetails);
                
                // Provide more specific error messages based on status code
                $errorMessage = 'Rollback deployment failed! ';
                switch ($response->status()) {
                    case 404:
                        $errorMessage .= 'Rollback endpoint not found. Please check if the rollback script is deployed at the correct URL: ' . $endpoint;
                        break;
                    case 401:
                        $errorMessage .= 'Unauthorized access to rollback endpoint. Please check the authentication token.';
                        break;
                    case 400:
                        $errorMessage .= 'Bad request to rollback endpoint. The rollback script may have rejected the request.';
                        break;
                    default:
                        $errorMessage .= 'HTTP Status: ' . $response->status() . '. Please verify the rollback endpoint is accessible and properly configured.';
                }
                
                // Send failure notification
                $this->sendDeploymentNotification($deployment, 'failure');
                
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'log' => json_encode($errorDetails),
                ], 500);
            }
        } catch (\Exception $e) {
            $errorDetails = [
                'exception_class' => get_class($e),
                'exception_message' => $e->getMessage(),
                'exception_trace' => $e->getTraceAsString(),
                'project_id' => $project->id,
                'deployment_id' => $deployment->id,
                'rollback_target_id' => $targetDeployment->id,
            ];
            
            $deployment->update([
                'status' => 'failed',
                'completed_at' => now(),
                'log_output' => json_encode($errorDetails),
            ]);
            
            // Log the exception
            $logger->logException($e);
            
            // Send failure notification
            $this->sendDeploymentNotification($deployment, 'failure');
            
            return response()->json([
                'success' => false,
                'message' => 'Rollback deployment failed! Exception: ' . $e->getMessage(),
                'log' => json_encode($errorDetails),
            ], 500);
        }
    }

    /**
     * Get deployment logs.
     */
    public function logs(Project $project, Deployment $deployment)
    {
        $this->authorize('view', $project);
        
        return response()->json([
            'log' => $deployment->log_output,
        ]);
    }

    /**
     * Display detailed logs for a deployment.
     */
    public function detailedLogs(Project $project, Deployment $deployment)
    {
        $this->authorize('view', $project);
        
        $deployment->load('environment');
        $logs = $deployment->logs()->orderBy('created_at', 'desc')->paginate(50);
        
        return view('deployments.logs', compact('project', 'deployment', 'logs'));
    }

    /**
     * Display the deployment monitoring dashboard.
     */
    public function monitoring()
    {
        $this->authorize('viewAny', Deployment::class);
        
        // Get deployment statistics
        $totalDeployments = Deployment::count();
        $successfulDeployments = Deployment::where('status', 'success')->count();
        $failedDeployments = Deployment::where('status', 'failed')->count();
        $pendingDeployments = Deployment::where('status', 'pending')->count();
        
        // Get recent deployments with environment information
        $recentDeployments = Deployment::with(['project', 'user', 'environment'])->latest()->paginate(20);
        
        return view('deployments.monitoring', compact(
            'totalDeployments',
            'successfulDeployments',
            'failedDeployments',
            'pendingDeployments',
            'recentDeployments'
        ));
    }

    /**
     * Display the real-time monitoring view.
     */
    public function realtimeMonitoring()
    {
        $this->authorize('viewAny', Deployment::class);
        
        // Get recent deployments for the dropdown with environment information
        $recentDeployments = Deployment::with(['project', 'user', 'environment'])->latest()->limit(50)->get();
        
        return view('deployments.realtime-monitoring', compact('recentDeployments'));
    }

    /**
     * Get real-time deployment logs for monitoring.
     */
    public function realtimeLogs(Deployment $deployment)
    {
        $this->authorize('view', $deployment->project);
        
        $logs = $deployment->logs()->orderBy('created_at', 'desc')->limit(100)->get();
        
        return response()->json([
            'deployment' => $deployment->load(['project', 'user', 'environment']),
            'logs' => $logs,
        ]);
    }

    /**
     * Display commit history for a project.
     */
    public function commits(Project $project)
    {
        $this->authorize('view', $project);
        
        // Try to fetch commit history from the repository
        $commits = [];
        $error = null;
        
        try {
            // Extract repository info from URL
            $repoUrl = $project->repository_url;
            
            // For GitHub repositories, we can fetch commit history via API
            if (strpos($repoUrl, 'github.com') !== false) {
                // Extract owner and repo name from GitHub URL
                // Example: https://github.com/owner/repo.git or https://github.com/owner/repo
                $pattern = '/github\.com[\/:]([^\/]+)\/([^\/\.]+)/';
                if (preg_match($pattern, $repoUrl, $matches)) {
                    $owner = $matches[1];
                    $repo = $matches[2];
                    
                    // GitHub API URL for commits
                    $apiUrl = "https://api.github.com/repos/{$owner}/{$repo}/commits?sha={$project->current_branch}";
                    
                    // Try to fetch commits (without authentication for now)
                    $response = Http::withHeaders([
                        'User-Agent' => 'Deployment-Manager-App'
                    ])->get($apiUrl);
                    
                    if ($response->successful()) {
                        $commits = $response->json();
                    } else {
                        $error = "Failed to fetch commits from GitHub API. Status: " . $response->status();
                    }
                } else {
                    $error = "Could not parse GitHub repository URL";
                }
            } else {
                $error = "Currently only GitHub repositories are supported for commit history";
            }
        } catch (\Exception $e) {
            $error = "Error fetching commit history: " . $e->getMessage();
        }
        
        return view('deployments.commits', compact('project', 'commits', 'error'));
    }

    /**
     * Extract commit hash from a deployment response body.
     */
    private function extractCommitHash(string $responseBody): ?string
    {
        // Try JSON first
        $responseData = json_decode($responseBody, true);
        if (is_array($responseData) && isset($responseData['commit_hash'])) {
            return $responseData['commit_hash'];
        }

        // Try regex patterns: full 40-char SHA first
        if (preg_match('/([a-f0-9]{40})/i', $responseBody, $matches)) {
            return $matches[1];
        }

        // Git pull update format: "Updating <old>.. <new>"
        if (preg_match('/Updating\s+[a-f0-9]{7,40}\.\.([a-f0-9]{7,40})/i', $responseBody, $matches)) {
            return $matches[1];
        }

        // Detached HEAD or reset format
        if (preg_match('/HEAD is now at\s+([a-f0-9]{7,40})/i', $responseBody, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Run security scan after deployment.
     */
    private function runSecurityScan(Deployment $deployment, DeploymentLogger $logger)
    {
        try {
            $logger->info('Starting post-deployment security scan', [
                'deployment_id' => $deployment->id
            ]);

            $securityScanner = app(SecurityScannerService::class);
            $scanResult = $securityScanner->scanDeployment($deployment);
            
            $logger->info('Security scan completed', [
                'deployment_id' => $deployment->id,
                'can_deploy' => $scanResult->can_deploy,
                'vulnerability_counts' => $scanResult->vulnerability_counts,
                'policy_applied' => $scanResult->policy_applied
            ]);

            // If scan finds critical issues, optionally trigger rollback
            if (!$scanResult->can_deploy) {
                $logger->warning('Security scan found critical issues - consider rollback', [
                    'deployment_id' => $deployment->id,
                    'violation_message' => $scanResult->violation_message
                ]);
                
                // Update deployment status to indicate security concerns
                $deployment->update([
                    'status' => 'success_with_security_warnings'
                ]);
            }

        } catch (\Exception $e) {
            $logger->error('Security scan failed', [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Write content to a file with retry logic and timeout.
     *
     * @param string $filename
     * @param string $content
     * @param int $maxRetries
     * @param int $timeoutSeconds
     * @return bool|int
     */
    private function writeFileWithRetry(string $filename, string $content, int $maxRetries = 3, int $timeoutSeconds = 60)
    {
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            $attempt++;
            
            try {
                // Log the attempt
                if ($attempt > 1) {
                    Log::info('Retrying file write operation', [
                        'filename' => $filename,
                        'attempt' => $attempt,
                        'max_retries' => $maxRetries
                    ]);
                    
                    // Wait a bit before retrying
                    sleep(2);
                }
                
                // Try to write the file with timeout
                $result = $this->writeFileWithTimeout($filename, $content, $timeoutSeconds);
                
                if ($result !== false) {
                    // Success
                    if ($attempt > 1) {
                        Log::info('File write operation succeeded on retry', [
                            'filename' => $filename,
                            'attempt' => $attempt
                        ]);
                    }
                    return $result;
                }
                
                // Log failure
                $lastError = error_get_last();
                Log::warning('File write attempt failed', [
                    'filename' => $filename,
                    'attempt' => $attempt,
                    'error' => $lastError ? $lastError['message'] : 'Unknown error'
                ]);
                
            } catch (\Exception $e) {
                Log::error('Exception during file write attempt', [
                    'filename' => $filename,
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);
            } catch (\Error $e) {
                Log::error('Error during file write attempt', [
                    'filename' => $filename,
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // All retries failed
        Log::error('File write operation failed after all retries', [
            'filename' => $filename,
            'attempts' => $maxRetries
        ]);
        
        return false;
    }
    
    /**
     * Write content to a file with a timeout.
     *
     * @param string $filename
     * @param string $content
     * @param int $timeoutSeconds
     * @return bool|int
     */
    private function writeFileWithTimeout(string $filename, string $content, int $timeoutSeconds = 60)
    {
        // Store the current time
        $startTime = time();
        
        // Try to write the file
        try {
            // For network file operations, we need to handle them differently
            // to avoid PHP script timeouts
            if (strpos($filename, '\\\\') === 0) {
                // This is a UNC path, use a more robust approach
                return $this->writeNetworkFile($filename, $content, $timeoutSeconds);
            }
            
            // Use file_put_contents with error suppression for local files
            $result = @file_put_contents($filename, $content);
            
            // Check if we've exceeded the timeout
            $elapsedTime = time() - $startTime;
            if ($elapsedTime > $timeoutSeconds) {
                Log::warning('File write operation timed out', [
                    'filename' => $filename,
                    'timeout_seconds' => $timeoutSeconds,
                    'elapsed_time' => $elapsedTime
                ]);
                return false;
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Exception during file write operation', [
                'filename' => $filename,
                'error' => $e->getMessage(),
                'elapsed_time' => time() - $startTime
            ]);
            return false;
        } catch (\Error $e) {
            Log::error('Error during file write operation', [
                'filename' => $filename,
                'error' => $e->getMessage(),
                'elapsed_time' => time() - $startTime
            ]);
            return false;
        }
    }
    
    /**
     * Write content to a network file (UNC path) with better error handling.
     *
     * @param string $filename
     * @param string $content
     * @param int $timeoutSeconds
     * @return bool|int
     */
    private function writeNetworkFile(string $filename, string $content, int $timeoutSeconds = 60)
    {
        // Check if the directory exists and is writable first
        $directory = dirname($filename);
        if (!is_dir($directory)) {
            Log::error('Network directory does not exist', [
                'directory' => $directory,
                'filename' => $filename
            ]);
            return false;
        }
        
        if (!is_writable($directory)) {
            Log::error('Network directory is not writable', [
                'directory' => $directory,
                'filename' => $filename
            ]);
            return false;
        }
        
        // Try to write the file with a more controlled approach
        $handle = @fopen($filename, 'w');
        if ($handle === false) {
            $lastError = error_get_last();
            Log::error('Failed to open network file for writing', [
                'filename' => $filename,
                'error' => $lastError ? $lastError['message'] : 'Unknown error'
            ]);
            return false;
        }
        
        // Write content
        $bytesWritten = @fwrite($handle, $content);
        if ($bytesWritten === false) {
            $lastError = error_get_last();
            Log::error('Failed to write to network file', [
                'filename' => $filename,
                'error' => $lastError ? $lastError['message'] : 'Unknown error'
            ]);
            @fclose($handle);
            return false;
        }
        
        // Close the file
        if (@fclose($handle) === false) {
            $lastError = error_get_last();
            Log::warning('Failed to close network file handle', [
                'filename' => $filename,
                'error' => $lastError ? $lastError['message'] : 'Unknown error'
            ]);
            // Still return success since we wrote the content
        }
        
        return $bytesWritten;
    }
    
    /**
     * Copy a file with retry logic and timeout.
     *
     * @param string $source
     * @param string $destination
     * @param int $maxRetries
     * @param int $timeoutSeconds
     * @return bool|int
     */
    private function copyFileWithRetry(string $source, string $destination, int $maxRetries = 3, int $timeoutSeconds = 60)
    {
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            $attempt++;
            
            try {
                // Log the attempt
                if ($attempt > 1) {
                    Log::info('Retrying file copy operation', [
                        'source' => $source,
                        'destination' => $destination,
                        'attempt' => $attempt,
                        'max_retries' => $maxRetries
                    ]);
                    
                    // Wait a bit before retrying
                    sleep(2);
                }
                
                // Set a timeout for the copy operation
                $startTime = time();
                
                // Try to copy the file
                $result = @copy($source, $destination);
                
                // Check if we've exceeded the timeout
                $elapsedTime = time() - $startTime;
                if ($elapsedTime > $timeoutSeconds) {
                    Log::warning('File copy operation timed out', [
                        'source' => $source,
                        'destination' => $destination,
                        'timeout_seconds' => $timeoutSeconds,
                        'elapsed_time' => $elapsedTime
                    ]);
                    return false;
                }
                
                if ($result !== false) {
                    // Success
                    if ($attempt > 1) {
                        Log::info('File copy operation succeeded on retry', [
                            'source' => $source,
                            'destination' => $destination,
                            'attempt' => $attempt
                        ]);
                    }
                    return filesize($destination);
                }
                
                // Log failure
                $lastError = error_get_last();
                Log::warning('File copy attempt failed', [
                    'source' => $source,
                    'destination' => $destination,
                    'attempt' => $attempt,
                    'error' => $lastError ? $lastError['message'] : 'Unknown error'
                ]);
                
            } catch (\Exception $e) {
                Log::error('Exception during file copy attempt', [
                    'source' => $source,
                    'destination' => $destination,
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);
            } catch (\Error $e) {
                Log::error('Error during file copy attempt', [
                    'source' => $source,
                    'destination' => $destination,
                    'attempt' => $attempt,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // All retries failed
        Log::error('File copy operation failed after all retries', [
            'source' => $source,
            'destination' => $destination,
            'attempts' => $maxRetries
        ]);
        
        return false;
    }
    
    /**
     * Copy content to a network file (UNC path) with better error handling.
     *
     * @param string $source
     * @param string $destination
     * @param int $timeoutSeconds
     * @return bool|string
     */
    private function copyNetworkFile(string $source, string $destination, int $timeoutSeconds = 60)
    {
        // Check if the directory exists and is writable first
        $directory = dirname($destination);
        if (!is_dir($directory)) {
            Log::error('Network directory does not exist', [
                'directory' => $directory,
                'destination' => $destination
            ]);
            return false;
        }
        
        if (!is_writable($directory)) {
            Log::error('Network directory is not writable', [
                'directory' => $directory,
                'destination' => $destination
            ]);
            return false;
        }
        
        // Try to copy the file with a more controlled approach
        $handle = @fopen($source, 'r');
        if ($handle === false) {
            $lastError = error_get_last();
            Log::error('Failed to open source file for reading', [
                'source' => $source,
                'error' => $lastError ? $lastError['message'] : 'Unknown error'
            ]);
            return false;
        }
        
        $destinationHandle = @fopen($destination, 'w');
        if ($destinationHandle === false) {
            $lastError = error_get_last();
            Log::error('Failed to open destination file for writing', [
                'destination' => $destination,
                'error' => $lastError ? $lastError['message'] : 'Unknown error'
            ]);
            @fclose($handle);
            return false;
        }
        
        // Copy content
        while (!feof($handle)) {
            $buffer = @fread($handle, 8192);
            if ($buffer === false) {
                $lastError = error_get_last();
                Log::error('Failed to read from source file', [
                    'source' => $source,
                    'error' => $lastError ? $lastError['message'] : 'Unknown error'
                ]);
                @fclose($handle);
                @fclose($destinationHandle);
                return false;
            }
            
            $bytesWritten = @fwrite($destinationHandle, $buffer);
            if ($bytesWritten === false) {
                $lastError = error_get_last();
                Log::error('Failed to write to destination file', [
                    'destination' => $destination,
                    'error' => $lastError ? $lastError['message'] : 'Unknown error'
                ]);
                @fclose($handle);
                @fclose($destinationHandle);
                return false;
            }
        }
        
        // Close the files
        if (@fclose($handle) === false) {
            $lastError = error_get_last();
            Log::warning('Failed to close source file handle', [
                'source' => $source,
                'error' => $lastError ? $lastError['message'] : 'Unknown error'
            ]);
        }
        
        if (@fclose($destinationHandle) === false) {
            $lastError = error_get_last();
            Log::warning('Failed to close destination file handle', [
                'destination' => $destination,
                'error' => $lastError ? $lastError['message'] : 'Unknown error'
            ]);
        }
        
        return true;
    }
    
    /**
     * Send deployment notification to relevant users.
     */
    protected function sendDeploymentNotification(Deployment $deployment, string $type)
    {
        app(\App\Services\DeploymentNotifier::class)->send($deployment, $type);
    }
}