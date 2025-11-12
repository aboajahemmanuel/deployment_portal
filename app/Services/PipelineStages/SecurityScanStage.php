<?php

namespace App\Services\PipelineStages;

use App\Models\Deployment;
use App\Models\PipelineStage;
use App\Services\SecurityScannerService;
use Illuminate\Support\Facades\Log;
use Exception;

class SecurityScanStage
{
    protected SecurityScannerService $securityScanner;

    public function __construct(SecurityScannerService $securityScanner)
    {
        $this->securityScanner = $securityScanner;
    }

    /**
     * Execute the security scan stage.
     */
    public function execute(Deployment $deployment, PipelineStage $stage): bool
    {
        $stage->markAsStarted();
        
        Log::info("Starting security scan stage for deployment {$deployment->id}");

        try {
            // Run security scans
            $scanResult = $this->securityScanner->scanDeployment($deployment);
            
            // Update stage with scan summary
            $output = $this->generateScanOutput($scanResult);
            
            if ($scanResult->can_deploy) {
                $stage->markAsSuccess($output);
                Log::info("Security scan passed for deployment {$deployment->id}");
                return true;
            } else {
                $stage->markAsFailed($scanResult->violation_message, $output);
                Log::warning("Security scan failed for deployment {$deployment->id}", [
                    'violations' => $scanResult->violation_message
                ]);
                return false;
            }
            
        } catch (Exception $e) {
            $errorMessage = "Security scan error: " . $e->getMessage();
            $stage->markAsFailed($errorMessage);
            Log::error("Security scan stage failed", [
                'deployment_id' => $deployment->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Generate output summary for the security scan.
     */
    protected function generateScanOutput($scanResult): string
    {
        $output = "Security Scan Results:\n";
        $output .= "Policy Applied: {$scanResult->policy_applied}\n\n";
        
        $output .= "Vulnerability Summary:\n";
        foreach ($scanResult->vulnerability_counts as $severity => $count) {
            if ($count > 0) {
                $output .= "- " . ucfirst($severity) . ": {$count}\n";
            }
        }
        
        $output .= "\nScan Types Executed:\n";
        foreach ($scanResult->scan_results as $scanType => $results) {
            $output .= "- " . strtoupper($scanType) . ": " . count($results) . " findings\n";
        }
        
        if (!$scanResult->can_deploy) {
            $output .= "\n⚠️ DEPLOYMENT BLOCKED\n";
            $output .= "Reason: {$scanResult->violation_message}\n";
        } else {
            $output .= "\n✅ SECURITY SCAN PASSED\n";
        }
        
        return $output;
    }

    /**
     * Create security scan pipeline stages for a deployment.
     */
    public static function createStages(Deployment $deployment): array
    {
        $stages = [];
        $project = $deployment->project;
        
        // Get security policy to determine required scans
        $policy = $project->securityPolicy ?? null;
        $requiredScans = $policy ? $policy->required_scan_types : ['sast', 'dependency', 'secrets'];
        
        $baseOrder = 100; // Security scans should run early in pipeline
        
        // Create individual scan stages
        foreach ($requiredScans as $index => $scanType) {
            $stages[] = [
                'deployment_id' => $deployment->id,
                'name' => "security-scan-{$scanType}",
                'display_name' => self::getScanDisplayName($scanType),
                'description' => self::getScanDescription($scanType),
                'order' => $baseOrder + $index,
                'status' => 'pending',
                'metadata' => [
                    'scan_type' => $scanType,
                    'stage_type' => 'security_scan'
                ]
            ];
        }
        
        // Create summary/evaluation stage
        $stages[] = [
            'deployment_id' => $deployment->id,
            'name' => 'security-evaluation',
            'display_name' => 'Security Policy Evaluation',
            'description' => 'Evaluate scan results against security policy',
            'order' => $baseOrder + count($requiredScans),
            'status' => 'pending',
            'metadata' => [
                'stage_type' => 'security_evaluation'
            ]
        ];
        
        return $stages;
    }

    /**
     * Get display name for scan type.
     */
    protected static function getScanDisplayName(string $scanType): string
    {
        return match($scanType) {
            'sast' => 'Static Code Analysis',
            'dependency' => 'Dependency Vulnerability Scan',
            'secrets' => 'Secret Detection',
            'infrastructure' => 'Infrastructure Security Scan',
            'container' => 'Container Security Scan',
            default => ucfirst($scanType) . ' Security Scan'
        };
    }

    /**
     * Get description for scan type.
     */
    protected static function getScanDescription(string $scanType): string
    {
        return match($scanType) {
            'sast' => 'Analyze source code for security vulnerabilities and coding issues',
            'dependency' => 'Check dependencies for known security vulnerabilities',
            'secrets' => 'Scan for hardcoded secrets, API keys, and credentials',
            'infrastructure' => 'Analyze infrastructure configuration for security issues',
            'container' => 'Scan container images for vulnerabilities',
            default => "Run {$scanType} security analysis"
        };
    }
}
