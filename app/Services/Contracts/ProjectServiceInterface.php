<?php

namespace App\Services\Contracts;

use App\Models\Project;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ProjectServiceInterface
{
    /**
     * Create a new project.
     */
    public function createProject(array $data, User $user): Project;

    /**
     * Update an existing project.
     */
    public function updateProject(Project $project, array $data): Project;

    /**
     * Delete a project and its related data.
     */
    public function deleteProject(Project $project): bool;

    /**
     * Get projects accessible by a user.
     */
    public function getProjectsForUser(User $user): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get paginated projects with filters.
     */
    public function getPaginatedProjects(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Validate project configuration.
     */
    public function validateProjectConfig(array $data): array;

    /**
     * Test project connectivity.
     */
    public function testProjectConnectivity(Project $project): array;

    /**
     * Get project deployment statistics.
     */
    public function getProjectStats(Project $project): array;

    /**
     * Archive/deactivate a project.
     */
    public function archiveProject(Project $project): bool;

    /**
     * Restore an archived project.
     */
    public function restoreProject(Project $project): bool;
}
