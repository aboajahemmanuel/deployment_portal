<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use App\Services\Contracts\ProjectServiceInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Exception;

class ProjectService implements ProjectServiceInterface
{
    /**
     * Create a new project.
     */
    public function createProject(array $data, User $user): Project
    {
        $validatedData = $this->validateProjectConfig($data);
        
        // Assign project to user if they're a developer
        if ($user->hasRole('developer')) {
            $validatedData['user_id'] = $user->id;
        }

        $project = Project::create($validatedData);

        Log::info('Project created', [
            'project_id' => $project->id,
            'project_name' => $project->name,
            'created_by' => $user->id,
        ]);

        return $project;
    }

    /**
     * Update an existing project.
     */
    public function updateProject(Project $project, array $data): Project
    {
        $validatedData = $this->validateProjectConfig($data);
        
        $project->update($validatedData);

        Log::info('Project updated', [
            'project_id' => $project->id,
            'project_name' => $project->name,
            'updated_by' => Auth::id(),
        ]);

        return $project->fresh();
    }

    /**
     * Delete a project and its related data.
     */
    public function deleteProject(Project $project): bool
    {
        try {
            $projectId = $project->id;
            $projectName = $project->name;
            
            // Soft delete will cascade to related models due to foreign key constraints
            $project->delete();

            Log::info('Project deleted', [
                'project_id' => $projectId,
                'project_name' => $projectName,
                'deleted_by' => Auth::id(),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to delete project', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get projects accessible by a user.
     */
    public function getProjectsForUser(User $user): \Illuminate\Database\Eloquent\Collection
    {
        if ($user->hasRole('admin')) {
            return Project::with('latestDeployment')->get();
        }
        
        if ($user->hasRole('developer')) {
            return Project::where('user_id', $user->id)
                ->with('latestDeployment')
                ->get();
        }

        return collect();
    }

    /**
     * Get paginated projects with filters.
     */
    public function getPaginatedProjects(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Project::with(['latestDeployment', 'user']);
        
        // Apply user-based filtering
        $user = Auth::user();
        if ($user && !$user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        // Apply filters
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('repository_url', 'like', "%{$search}%");
            });
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Validate project configuration.
     */
    public function validateProjectConfig(array $data): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'repository_url' => 'required|url',
            'deploy_endpoint' => 'required|url',
            'rollback_endpoint' => 'nullable|url',
            'access_token' => 'required|string',
            'current_branch' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];

        $validator = validator($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Test project connectivity.
     */
    public function testProjectConnectivity(Project $project): array
    {
        $results = [
            'deploy_endpoint' => $this->testEndpoint($project->deploy_endpoint, $project->access_token),
            'rollback_endpoint' => null,
            'repository' => $this->testRepositoryAccess($project->repository_url),
        ];

        if ($project->rollback_endpoint) {
            $results['rollback_endpoint'] = $this->testEndpoint($project->rollback_endpoint, $project->access_token);
        }

        return $results;
    }

    /**
     * Get project deployment statistics.
     */
    public function getProjectStats(Project $project): array
    {
        $deployments = $project->deployments();
        $totalDeployments = $deployments->count();
        
        if ($totalDeployments === 0) {
            return [
                'total_deployments' => 0,
                'successful_deployments' => 0,
                'failed_deployments' => 0,
                'success_rate' => 0,
                'last_deployment' => null,
                'average_duration' => 0,
                'deployment_frequency' => 0,
            ];
        }

        $successfulDeployments = $deployments->where('status', 'success')->count();
        $failedDeployments = $deployments->where('status', 'failed')->count();
        $lastDeployment = $project->latestDeployment;

        // Calculate average duration
        $completedDeployments = $deployments->whereNotNull('completed_at')->get();
        $averageDuration = 0;
        if ($completedDeployments->count() > 0) {
            $totalDuration = $completedDeployments->sum(function ($deployment) {
                return $deployment->started_at->diffInSeconds($deployment->completed_at);
            });
            $averageDuration = round($totalDuration / $completedDeployments->count());
        }

        // Calculate deployment frequency (deployments per week)
        $firstDeployment = $deployments->oldest()->first();
        $deploymentFrequency = 0;
        if ($firstDeployment) {
            $weeksSinceFirst = $firstDeployment->created_at->diffInWeeks(now()) ?: 1;
            $deploymentFrequency = round($totalDeployments / $weeksSinceFirst, 2);
        }

        return [
            'total_deployments' => $totalDeployments,
            'successful_deployments' => $successfulDeployments,
            'failed_deployments' => $failedDeployments,
            'success_rate' => $totalDeployments > 0 ? round(($successfulDeployments / $totalDeployments) * 100, 2) : 0,
            'last_deployment' => $lastDeployment,
            'average_duration' => $averageDuration,
            'deployment_frequency' => $deploymentFrequency,
        ];
    }

    /**
     * Archive/deactivate a project.
     */
    public function archiveProject(Project $project): bool
    {
        try {
            $project->update(['is_active' => false]);

            Log::info('Project archived', [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'archived_by' => Auth::id(),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to archive project', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Restore an archived project.
     */
    public function restoreProject(Project $project): bool
    {
        try {
            $project->update(['is_active' => true]);

            Log::info('Project restored', [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'restored_by' => Auth::id(),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to restore project', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Test endpoint connectivity.
     */
    private function testEndpoint(string $endpoint, string $token): array
    {
        try {
            $response = Http::withToken($token)
                ->timeout(10)
                ->withOptions(['verify' => false])
                ->get($endpoint, ['test' => true]);

            return [
                'status' => 'success',
                'response_code' => $response->status(),
                'response_time' => $response->transferStats?->getTransferTime() ?? 0,
                'message' => 'Endpoint is accessible',
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'response_code' => null,
                'response_time' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Test repository access.
     */
    private function testRepositoryAccess(string $repositoryUrl): array
    {
        try {
            // For GitHub repositories, test API access
            if (strpos($repositoryUrl, 'github.com') !== false) {
                $pattern = '/github\.com[\/:]([^\/]+)\/([^\/\.]+)/';
                if (preg_match($pattern, $repositoryUrl, $matches)) {
                    $owner = $matches[1];
                    $repo = $matches[2];
                    
                    $apiUrl = "https://api.github.com/repos/{$owner}/{$repo}";
                    $response = Http::withHeaders(['User-Agent' => 'Deployment-Manager-App'])
                        ->timeout(10)
                        ->get($apiUrl);

                    if ($response->successful()) {
                        return [
                            'status' => 'success',
                            'message' => 'Repository is accessible',
                            'details' => $response->json(),
                        ];
                    } else {
                        return [
                            'status' => 'error',
                            'message' => 'Repository not accessible or private',
                            'response_code' => $response->status(),
                        ];
                    }
                }
            }

            return [
                'status' => 'warning',
                'message' => 'Repository access test not implemented for this provider',
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }
}
