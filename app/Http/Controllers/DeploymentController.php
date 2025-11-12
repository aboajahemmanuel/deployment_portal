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
            $projects = Project::with('latestDeployment')->get();
            $upcomingScheduledDeployments = ScheduledDeployment::with(['project', 'user'])
                ->where('status', 'pending')
                ->where('scheduled_at', '>', now())
                ->orderBy('scheduled_at', 'asc')
                ->limit(5)
                ->get();
        } elseif ($user->roles->contains('name', 'developer')) {
            // Developers only see their assigned projects
            $projects = Project::where('user_id', $user->id)
                ->with('latestDeployment')
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
            'deploy_endpoint' => 'required|url',
            'rollback_endpoint' => 'nullable|url',
            'access_token' => 'required|string',
            'current_branch' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        // Assign project to current user if they're a developer
        $user = Auth::user();
        if ($user->roles->contains('name', 'developer')) {
            $validated['user_id'] = $user->id;
        }

        $project = Project::create($validated);

        return redirect()->route('deployments.index')
            ->with('success', 'Project created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        $this->authorize('view', $project);
        
        $deployments = $project->deployments()->latest()->paginate(10);
        
        return view('deployments.show', compact('project', 'deployments'));
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
            'deploy_endpoint' => 'required|url',
            'rollback_endpoint' => 'nullable|url',
            'access_token' => 'required|string',
            'current_branch' => 'required|string|max:255',
            'description' => 'nullable|string',
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
    public function deploy(Project $project)
    {
        try {
            $this->authorize('deploy', $project);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return redirect()->back()->with('error', 'You are not authorized to deploy this project. Only project owners and administrators can trigger deployments.');
        }
        
        // Create deployment record
        $deployment = Deployment::create([
            'project_id' => $project->id,
            'user_id' => Auth::id(),
            'commit_hash' => 'pending',
            'status' => 'processing',
            'started_at' => now(),
        ]);

        // Create pipeline stages for this deployment
        app(\App\Services\PipelineStageManager::class)->createStagesForDeployment($deployment);

        // Create a logger for this deployment
        $logger = new DeploymentLogger($deployment);

        try {
            // Log the deployment attempt
            $logger->info('Deployment attempt started', [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'deploy_endpoint' => $project->deploy_endpoint,
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'deployment_id' => $deployment->id,
            ]);

            // Prepare request parameters
            $params = [
                'project_id' => $project->id,
                'branch' => $project->current_branch,
                'user_id' => Auth::id(),
                'deployment_id' => $deployment->id,
            ];

            // Log the parameters being sent
            $logger->info('Sending deployment request with parameters', $params);

            // Log the HTTP request details
            $logger->logHttpRequest($project->deploy_endpoint, 'GET', [], $params);

            // Send request to deploy endpoint with SSL verification options
            $response = Http::withToken($project->access_token)
                ->timeout(300) // 5 minute timeout
                ->withOptions([
                    'verify' => false, // Disable SSL verification for now - in production, you should properly configure SSL certificates
                ])
                ->get($project->deploy_endpoint, $params);

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
                        } else {
                            // Try to extract short commit hash from "HEAD is now at c9fbcb5" pattern
                            if (preg_match('/HEAD is now at ([a-f0-9]{7,40})/', $responseBody, $matches)) {
                                $commitHash = $matches[1];
                            }
                        }
                    }
                }
                
                $deployment->update([
                    'status' => 'success',
                    'completed_at' => now(),
                    'log_output' => $response->body(),
                    'commit_hash' => $commitHash,
                ]);
                
                $logger->info('Deployment successful', [
                    'project_id' => $project->id,
                    'deployment_id' => $deployment->id,
                    'response_body' => $response->body(),
                    'commit_hash' => $commitHash,
                ]);

                // Update pipeline stages to reflect successful deployment
                $stageManager = app(\App\Services\PipelineStageManager::class);
                $stageManager->simulateExecution($deployment);

                // Run security scan after successful deployment
                $this->runSecurityScan($deployment, $logger);
                
                // Send success notification
                $this->sendDeploymentNotification($deployment, 'success');
                
                return response()->json([
                    'success' => true,
                    'message' => 'Deployment successful!',
                    'log' => $response->body(),
                ]);
            } else {
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

                // Update pipeline stages to reflect failed deployment
                $stageManager = app(\App\Services\PipelineStageManager::class);
                $stageManager->simulateExecution($deployment);
                
                // Send failure notification
                $this->sendDeploymentNotification($deployment, 'failure');
                
                return response()->json([
                    'success' => false,
                    'message' => 'Deployment failed! HTTP Status: ' . $response->status(),
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
                'message' => 'Deployment failed! Exception: ' . $e->getMessage(),
                'log' => json_encode($errorDetails),
            ], 500);
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

        // Create a new rollback deployment record
        $deployment = Deployment::create([
            'project_id' => $project->id,
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
                'deploy_endpoint' => $project->deploy_endpoint,
                'rollback_endpoint' => $project->rollback_endpoint,
                'user_id' => Auth::id(),
                'user_name' => Auth::user()->name,
                'deployment_id' => $deployment->id,
                'rollback_target_id' => $targetDeployment->id,
                'rollback_reason' => $rollbackReason,
            ]);

            // Determine which endpoint to use for rollback
            $endpoint = $project->rollback_endpoint;

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
        
        // Get recent deployments
        $recentDeployments = Deployment::with(['project', 'user'])->latest()->paginate(20);
        
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
        
        // Get recent deployments for the dropdown
        $recentDeployments = Deployment::with(['project', 'user'])->latest()->limit(50)->get();
        
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
            'deployment' => $deployment->load(['project', 'user']),
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
     * Send deployment notification to relevant users.
     */
    protected function sendDeploymentNotification(Deployment $deployment, string $type)
    {
        app(\App\Services\DeploymentNotifier::class)->send($deployment, $type);
    }
}