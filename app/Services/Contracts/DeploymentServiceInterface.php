<?php

namespace App\Services\Contracts;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\User;

interface DeploymentServiceInterface
{
    /**
     * Create a new deployment for a project.
     */
    public function createDeployment(Project $project, User $user, array $options = []): Deployment;

    /**
     * Execute a deployment.
     */
    public function executeDeployment(Deployment $deployment): bool;

    /**
     * Create a rollback deployment.
     */
    public function createRollback(Project $project, Deployment $targetDeployment, User $user, string $reason = ''): Deployment;

    /**
     * Execute a rollback deployment.
     */
    public function executeRollback(Deployment $rollbackDeployment): bool;

    /**
     * Get deployment statistics for a project.
     */
    public function getDeploymentStats(Project $project): array;

    /**
     * Get recent deployments with pagination.
     */
    public function getRecentDeployments(int $limit = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Cancel a pending deployment.
     */
    public function cancelDeployment(Deployment $deployment): bool;

    /**
     * Get deployment logs.
     */
    public function getDeploymentLogs(Deployment $deployment): array;
}
