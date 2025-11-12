<?php

namespace App\Services\Contracts;

use App\Models\Deployment;
use App\Models\Project;
use App\Models\SecurityPolicy;

interface SecurityServiceInterface
{
    /**
     * Scan a deployment for security vulnerabilities.
     */
    public function scanDeployment(Deployment $deployment, ?string $environment = 'production'): object;

    /**
     * Get security policy for a project.
     */
    public function getSecurityPolicy(Project $project, string $environment = 'production'): ?SecurityPolicy;

    /**
     * Create or update security policy.
     */
    public function createOrUpdatePolicy(array $data): SecurityPolicy;

    /**
     * Validate deployment against security policy.
     */
    public function validateDeployment(Deployment $deployment): bool;

    /**
     * Get vulnerability summary for a deployment.
     */
    public function getVulnerabilitySummary(Deployment $deployment): array;

    /**
     * Get security dashboard data.
     */
    public function getSecurityDashboard(): array;

    /**
     * Acknowledge a vulnerability.
     */
    public function acknowledgeVulnerability(int $resultId, string $reason): bool;

    /**
     * Mark vulnerability as false positive.
     */
    public function markFalsePositive(int $resultId, string $reason): bool;
}
