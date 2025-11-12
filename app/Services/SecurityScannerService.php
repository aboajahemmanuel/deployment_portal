<?php

namespace App\Services;

use App\Models\Deployment;
use App\Models\SecurityScanResult;
use App\Models\SecurityPolicy;
use App\Models\Project;
use App\Services\Contracts\SecurityServiceInterface;
use App\Services\SecurityScanners\SecurityScannerInterface;
use App\Services\SecurityScanners\SastScanner;
use App\Services\SecurityScanners\DependencyScanner;
use App\Services\SecurityScanners\SecretScanner;
use Illuminate\Support\Facades\Log;
use Exception;

class SecurityScannerService implements SecurityServiceInterface
{
    protected array $scanners = [];

    public function __construct()
    {
        $this->scanners = [
            'sast' => new SastScanner(),
            'dependency' => new DependencyScanner(),
            'secrets' => new SecretScanner(),
        ];
    }

    /**
     * Run security scans for a deployment.
     */
    public function scanDeployment(Deployment $deployment, ?string $environment = 'production'): object
    {
        $project = $deployment->project;
        $policy = $this->getSecurityPolicy($project, $environment);
        
        // Check if policy is active before running scan
        if (!$policy || !$policy->is_active) {
            Log::info("Skipping security scan - no active policy found", [
                'deployment_id' => $deployment->id,
                'project' => $project->name,
                'policy_active' => $policy ? $policy->is_active : false
            ]);
            
            return new SecurityScanResultDTO([
                'can_deploy' => true,
                'vulnerability_counts' => ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0, 'info' => 0],
                'violation_message' => null,
                'scan_results' => [],
                'policy_applied' => 'none - policy inactive or not found'
            ]);
        }
        
        Log::info("Starting security scan for deployment {$deployment->id}", [
            'project' => $project->name,
            'policy' => $policy->name ?? 'default',
            'required_scans' => $policy->required_scan_types ?? []
        ]);

        $allResults = [];
        $vulnerabilityCounts = [
            'critical' => 0,
            'high' => 0,
            'medium' => 0,
            'low' => 0,
            'info' => 0
        ];

        // Run each required scan type
        foreach ($policy->required_scan_types as $scanType) {
            if (!isset($this->scanners[$scanType])) {
                Log::warning("Scanner not available for type: {$scanType}");
                continue;
            }

            try {
                $results = $this->runScan($scanType, $deployment, $policy);
                $allResults[$scanType] = $results;
                
                // Count vulnerabilities by severity
                foreach ($results as $result) {
                    $vulnerabilityCounts[$result['severity']]++;
                }
                
                Log::info("Completed {$scanType} scan", [
                    'deployment_id' => $deployment->id,
                    'findings_count' => count($results)
                ]);
                
            } catch (Exception $e) {
                Log::error("Security scan failed for type {$scanType}", [
                    'deployment_id' => $deployment->id,
                    'error' => $e->getMessage()
                ]);
                
                // Store scan failure as a critical finding
                $allResults[$scanType] = [[
                    'scan_type' => $scanType,
                    'tool_name' => 'system',
                    'severity' => 'critical',
                    'title' => "Security scan failed: {$scanType}",
                    'description' => "Scanner error: " . $e->getMessage(),
                    'status' => 'open',
                    'first_detected_at' => now(),
                    'last_seen_at' => now(),
                ]];
                $vulnerabilityCounts['critical']++;
            }
        }

        // Store all results in database
        $this->storeResults($deployment, $allResults);

        // Evaluate against policy
        $canDeploy = $policy->allowsDeployment($vulnerabilityCounts);
        $violationMessage = $canDeploy ? null : $policy->getViolationMessage($vulnerabilityCounts);

        return new SecurityScanResultDTO([
            'can_deploy' => $canDeploy,
            'vulnerability_counts' => $vulnerabilityCounts,
            'violation_message' => $violationMessage,
            'scan_results' => $allResults,
            'policy_applied' => $policy->name ?? 'default'
        ]);
    }

    /**
     * Run a specific type of security scan.
     */
    protected function runScan(string $scanType, Deployment $deployment, SecurityPolicy $policy): array
    {
        $scanner = $this->scanners[$scanType];
        $project = $deployment->project;
        
        $scanConfig = [
            'project_path' => $this->getProjectPath($project),
            'repository_url' => $project->repository_url,
            'branch' => $project->current_branch ?? 'main',
            'timeout' => $policy->scan_timeout_minutes * 60,
            'max_retries' => $policy->max_retry_attempts,
        ];

        return $scanner->scan($scanConfig);
    }

    /**
     * Store scan results in the database.
     */
    protected function storeResults(Deployment $deployment, array $allResults): void
    {
        foreach ($allResults as $scanType => $results) {
            foreach ($results as $result) {
                SecurityScanResult::create([
                    'deployment_id' => $deployment->id,
                    'scan_type' => $scanType,
                    'tool_name' => $result['tool_name'] ?? 'unknown',
                    'severity' => $result['severity'],
                    'vulnerability_id' => $result['vulnerability_id'] ?? null,
                    'cve_id' => $result['cve_id'] ?? null,
                    'title' => $result['title'],
                    'description' => $result['description'] ?? null,
                    'file_path' => $result['file_path'] ?? null,
                    'line_number' => $result['line_number'] ?? null,
                    'code_snippet' => $result['code_snippet'] ?? null,
                    'remediation_advice' => $result['remediation_advice'] ?? null,
                    'reference_url' => $result['reference_url'] ?? null,
                    'status' => $result['status'] ?? 'open',
                    'metadata' => $result['metadata'] ?? null,
                    'first_detected_at' => $result['first_detected_at'] ?? now(),
                    'last_seen_at' => $result['last_seen_at'] ?? now(),
                ]);
            }
        }
    }

    /**
     * Get the security policy for a project.
     */
    public function getSecurityPolicy(Project $project, string $environment = 'production'): ?SecurityPolicy
    {
        // Get project-specific policy (active or inactive)
        $policy = SecurityPolicy::where('project_id', $project->id)
            ->first();
            
        if (!$policy) {
            // Get global default policy (active or inactive)
            $policy = SecurityPolicy::whereNull('project_id')
                ->first();
        }
        
        // Don't create a default policy automatically - let the caller handle inactive/missing policies
        return $policy;
    }

    /**
     * Get the local path for a project (for scanning).
     */
    protected function getProjectPath($project): string
    {
        // This would typically be a temporary checkout of the repository
        // For now, we'll use a placeholder path
        $basePath = storage_path('app/security-scans');
        $projectPath = $basePath . '/' . $project->id;
        
        if (!file_exists($projectPath)) {
            mkdir($projectPath, 0755, true);
        }
        
        return $projectPath;
    }

    /**
     * Get vulnerability summary for a deployment.
     */
    public function getVulnerabilitySummary(Deployment $deployment): array
    {
        $results = SecurityScanResult::where('deployment_id', $deployment->id);
        
        $total = $results->count();
        
        $bySeverity = [
            'critical' => $results->clone()->where('severity', 'critical')->count(),
            'high' => $results->clone()->where('severity', 'high')->count(),
            'medium' => $results->clone()->where('severity', 'medium')->count(),
            'low' => $results->clone()->where('severity', 'low')->count(),
            'info' => $results->clone()->where('severity', 'info')->count(),
        ];
        
        $byStatus = [
            'open' => $results->clone()->where('status', 'open')->count(),
            'acknowledged' => $results->clone()->where('status', 'acknowledged')->count(),
            'false_positive' => $results->clone()->where('status', 'false_positive')->count(),
            'fixed' => $results->clone()->where('status', 'fixed')->count(),
            'ignored' => $results->clone()->where('status', 'ignored')->count(),
        ];
        
        return [
            'total' => $total,
            'by_severity' => $bySeverity,
            'by_status' => $byStatus
        ];
    }

    /**
     * Check if deployment can proceed based on security scan results.
     */
    public function canDeploymentProceed(Deployment $deployment): bool
    {
        $policy = $this->getSecurityPolicy($deployment->project);
        $vulnerabilityCounts = $this->getVulnerabilitySummary($deployment)['by_severity'];
        
        return $policy->allowsDeployment($vulnerabilityCounts);
    }

    /**
     * Get blocking vulnerabilities for a deployment.
     */
    public function getBlockingVulnerabilities(Deployment $deployment): array
    {
        return SecurityScanResult::where('deployment_id', $deployment->id)
            ->whereIn('severity', ['critical', 'high'])
            ->where('status', 'open')
            ->get()
            ->map(function ($result) {
                return [
                    'severity' => $result->severity,
                    'title' => $result->title,
                    'description' => $result->description,
                    'file_path' => $result->file_path,
                    'line_number' => $result->line_number
                ];
            })
            ->toArray();
    }

    /**
     * Create or update security policy.
     */
    public function createOrUpdatePolicy(array $data): SecurityPolicy
    {
        if (isset($data['id'])) {
            $policy = SecurityPolicy::findOrFail($data['id']);
            $policy->update($data);
            return $policy;
        }

        return SecurityPolicy::create($data);
    }

    /**
     * Validate deployment against security policy.
     */
    public function validateDeployment(Deployment $deployment): bool
    {
        $policy = $this->getSecurityPolicy($deployment->project);
        if (!$policy || !$policy->is_active) {
            return true;
        }

        $vulnerabilityCounts = $this->getVulnerabilitySummary($deployment)['by_severity'];
        return $policy->allowsDeployment($vulnerabilityCounts);
    }

    /**
     * Get security dashboard data.
     */
    public function getSecurityDashboard(): array
    {
        $recentDeployments = Deployment::with(['project', 'securityScanResults'])
            ->latest()
            ->limit(10)
            ->get();

        $totalVulnerabilities = SecurityScanResult::count();
        $criticalVulnerabilities = SecurityScanResult::where('severity', 'critical')->count();
        $openVulnerabilities = SecurityScanResult::where('status', 'open')->count();

        return [
            'recent_deployments' => $recentDeployments,
            'vulnerability_stats' => [
                'total' => $totalVulnerabilities,
                'critical' => $criticalVulnerabilities,
                'open' => $openVulnerabilities,
                'by_severity' => [
                    'critical' => SecurityScanResult::where('severity', 'critical')->count(),
                    'high' => SecurityScanResult::where('severity', 'high')->count(),
                    'medium' => SecurityScanResult::where('severity', 'medium')->count(),
                    'low' => SecurityScanResult::where('severity', 'low')->count(),
                ],
            ],
            'policies' => SecurityPolicy::where('is_active', true)->count(),
        ];
    }

    /**
     * Acknowledge a vulnerability.
     */
    public function acknowledgeVulnerability(int $resultId, string $reason): bool
    {
        try {
            $result = SecurityScanResult::findOrFail($resultId);
            $result->update([
                'status' => 'acknowledged',
                'metadata' => array_merge($result->metadata ?? [], [
                    'acknowledged_at' => now(),
                    'acknowledged_reason' => $reason,
                ]),
            ]);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to acknowledge vulnerability', [
                'result_id' => $resultId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Mark vulnerability as false positive.
     */
    public function markFalsePositive(int $resultId, string $reason): bool
    {
        try {
            $result = SecurityScanResult::findOrFail($resultId);
            $result->update([
                'status' => 'false_positive',
                'metadata' => array_merge($result->metadata ?? [], [
                    'false_positive_at' => now(),
                    'false_positive_reason' => $reason,
                ]),
            ]);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to mark vulnerability as false positive', [
                'result_id' => $resultId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

/**
 * Data transfer object for security scan results.
 */
class SecurityScanResultDTO
{
    public bool $can_deploy;
    public array $vulnerability_counts;
    public ?string $violation_message;
    public array $scan_results;
    public string $policy_applied;

    public function __construct(array $data)
    {
        $this->can_deploy = $data['can_deploy'];
        $this->vulnerability_counts = $data['vulnerability_counts'];
        $this->violation_message = $data['violation_message'] ?? null;
        $this->scan_results = $data['scan_results'];
        $this->policy_applied = $data['policy_applied'];
    }
}
