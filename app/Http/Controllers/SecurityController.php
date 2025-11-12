<?php

namespace App\Http\Controllers;

use App\Models\Deployment;
use App\Models\SecurityScanResult;
use App\Models\SecurityPolicy;
use App\Services\SecurityScannerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SecurityController extends Controller
{
    protected SecurityScannerService $securityScanner;

    public function __construct(SecurityScannerService $securityScanner)
    {
        $this->middleware(['auth', 'role:admin|developer']);
        $this->securityScanner = $securityScanner;
    }

    /**
     * Display security dashboard.
     */
    public function dashboard()
    {
        $user = Auth::user();
        
        // Get recent security scan results from database
        $recentScans = SecurityScanResult::with(['deployment.project', 'deployment.user'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($result) {
                return (object)[
                    'id' => $result->id,
                    'deployment' => (object)[
                        'project' => (object)['name' => $result->deployment->project->name ?? 'Unknown Project'],
                        'user' => (object)['name' => $result->deployment->user->name ?? 'Unknown User']
                    ],
                    'scan_type' => $result->scan_type,
                    'severity' => $result->severity,
                    'severity_color' => $result->severity_color,
                    'title' => $result->title,
                    'status' => $result->status,
                    'created_at' => $result->created_at
                ];
            });

        // Get vulnerability statistics
        $vulnerabilityStats = $this->getVulnerabilityStats($user);
        
        // Get policy compliance
        $policyCompliance = $this->getPolicyCompliance($user);

        return view('security.dashboard', compact(
            'recentScans',
            'vulnerabilityStats', 
            'policyCompliance'
        ));
    }

    /**
     * Display security scan results for a deployment.
     */
    public function deploymentResults(Deployment $deployment)
    {
        $this->authorize('view', $deployment);
        
        $scanResults = SecurityScanResult::where('deployment_id', $deployment->id)
            ->orderBy('severity')
            ->orderBy('created_at')
            ->get()
            ->groupBy('scan_type');

        $summary = $this->securityScanner->getVulnerabilitySummary($deployment);
        $canDeploy = $this->securityScanner->canDeploymentProceed($deployment);
        $blockingVulns = $this->securityScanner->getBlockingVulnerabilities($deployment);

        return view('security.deployment-results', compact(
            'deployment',
            'scanResults',
            'summary',
            'canDeploy',
            'blockingVulns'
        ));
    }

    /**
     * Trigger manual security scan for a deployment.
     */
    public function triggerScan(Request $request, Deployment $deployment)
    {
        $this->authorize('view', $deployment);

        // Check if there's an active security policy before running scan
        $project = $deployment->project;
        $policy = SecurityPolicy::where('project_id', $project->id)->first() 
                  ?? SecurityPolicy::whereNull('project_id')->first();

        if (!$policy || !$policy->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot run security scan: No active security policy found for this project.',
                'data' => [
                    'policy_status' => $policy ? 'inactive' : 'not_found',
                    'policy_name' => $policy->name ?? null
                ]
            ], 422);
        }

        try {
            $scanResult = $this->securityScanner->scanDeployment($deployment);
            
            return response()->json([
                'success' => true,
                'message' => 'Security scan completed successfully',
                'data' => [
                    'can_deploy' => $scanResult->can_deploy,
                    'vulnerability_counts' => $scanResult->vulnerability_counts,
                    'policy_applied' => $scanResult->policy_applied,
                    'violation_message' => $scanResult->violation_message ?? null
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Security scan failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vulnerability details for modal display.
     */
    public function getVulnerabilityDetails(SecurityScanResult $result)
    {
        $this->authorize('view', $result->deployment);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $result->id,
                'title' => $result->title,
                'description' => $result->description,
                'severity' => $result->severity,
                'scan_type' => $result->scan_type,
                'file_path' => $result->file_path,
                'line_number' => $result->line_number,
                'code_snippet' => $result->code_snippet,
                'remediation_advice' => $result->remediation_advice,
                'reference_url' => $result->reference_url,
                'status' => $result->status,
                'cve_id' => $result->cve_id,
                'vulnerability_id' => $result->vulnerability_id,
                'metadata' => $result->metadata,
                'acknowledged_by' => $result->acknowledgedBy?->name,
                'acknowledged_at' => $result->acknowledged_at?->format('M d, Y H:i'),
                'acknowledgment_reason' => $result->acknowledgment_reason,
                'deployment' => [
                    'id' => $result->deployment->id,
                    'project_name' => $result->deployment->project->name,
                    'created_at' => $result->deployment->created_at->format('M d, Y H:i')
                ]
            ]
        ]);
    }

    /**
     * Acknowledge a security vulnerability.
     */
    public function acknowledgeVulnerability(Request $request, SecurityScanResult $result)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $result->acknowledge(Auth::user(), $request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Vulnerability acknowledged successfully'
        ]);
    }

    /**
     * Mark vulnerability as false positive.
     */
    public function markFalsePositive(Request $request, SecurityScanResult $result)
    {
        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $result->markAsFalsePositive(Auth::user(), $request->reason);

        return response()->json([
            'success' => true,
            'message' => 'Vulnerability marked as false positive'
        ]);
    }

    /**
     * Display security policies.
     */
    public function policies()
    {
        $user = Auth::user();
        
        // Get all security policies from database (both active and inactive)
        $policies = SecurityPolicy::with('project')
            ->get()
            ->map(function ($policy) {
                return (object)[
                    'id' => $policy->id,
                    'name' => $policy->name,
                    'description' => $policy->description,
                    'is_active' => $policy->is_active,
                    'max_critical_vulnerabilities' => $policy->max_critical_vulnerabilities,
                    'max_high_vulnerabilities' => $policy->max_high_vulnerabilities,
                    'project' => (object)['name' => $policy->project->name ?? 'Global Policy']
                ];
            });

        return view('security.policies', compact('policies'));
    }

    /**
     * Show form to create security policy.
     */
    public function createPolicy()
    {
        $projects = \App\Models\Project::all();
        
        return view('security.create-policy', compact('projects'));
    }

    /**
     * Store a new security policy.
     */
    public function storePolicy(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'project_id' => 'nullable|exists:projects,id',
                'is_active' => 'nullable|in:on,1,true,0,false',
                'max_critical_vulnerabilities' => 'required|integer|min:0',
                'max_high_vulnerabilities' => 'required|integer|min:0',
                'max_medium_vulnerabilities' => 'required|integer|min:0',
                'max_low_vulnerabilities' => 'required|integer|min:0',
                'required_scan_types' => 'required|array|min:1',
                'required_scan_types.*' => 'in:sast,dependency,secrets,infrastructure,container',
                'block_on_secrets' => 'nullable|in:on,1,true,0,false',
                'block_on_license_violations' => 'nullable|in:on,1,true,0,false',
                'scan_timeout_minutes' => 'required|integer|min:1',
                'max_retry_attempts' => 'required|integer|min:0',
                'notify_on_failure' => 'nullable|in:on,1,true,0,false',
                'notify_on_new_vulnerabilities' => 'nullable|in:on,1,true,0,false',
                'notification_channels' => 'nullable|array',
                'notification_channels.*' => 'in:email,slack,webhook'
            ]);

            // Prepare policy data with proper boolean handling
            $policyData = [
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'project_id' => $request->input('project_id') ?: null,
                'is_active' => $request->boolean('is_active', true),
                'max_critical_vulnerabilities' => $request->integer('max_critical_vulnerabilities'),
                'max_high_vulnerabilities' => $request->integer('max_high_vulnerabilities'),
                'max_medium_vulnerabilities' => $request->integer('max_medium_vulnerabilities'),
                'max_low_vulnerabilities' => $request->integer('max_low_vulnerabilities'),
                'required_scan_types' => $request->input('required_scan_types', []),
                'block_on_secrets' => $request->boolean('block_on_secrets', false),
                'block_on_license_violations' => $request->boolean('block_on_license_violations', false),
                'scan_timeout_minutes' => $request->integer('scan_timeout_minutes'),
                'max_retry_attempts' => $request->integer('max_retry_attempts'),
                'notify_on_failure' => $request->boolean('notify_on_failure', false),
                'notify_on_new_vulnerabilities' => $request->boolean('notify_on_new_vulnerabilities', false),
                'notification_channels' => $request->input('notification_channels', []),
            ];

            $policy = SecurityPolicy::create($policyData);
            
            // Check if request expects JSON (AJAX)
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Security policy created successfully',
                    'policy' => $policy
                ]);
            }
            
            return redirect()->route('security.policies')
                ->with('success', 'Security policy created successfully');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Check if request expects JSON (AJAX)
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
                
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Security policy creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            // Check if request expects JSON (AJAX)
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create security policy: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Failed to create security policy: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Run security scan manually for a deployment.
     */
    public function runScan(Deployment $deployment)
    {
        $this->authorize('update', $deployment);

        try {
            $result = $this->securityScanner->scanDeployment($deployment);
            
            return response()->json([
                'success' => true,
                'message' => 'Security scan completed',
                'can_deploy' => $result->can_deploy,
                'vulnerability_counts' => $result->vulnerability_counts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Security scan failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get vulnerability statistics.
     */
    protected function getVulnerabilityStats($user): array
    {
        // Get real vulnerability statistics from database
        $baseQuery = SecurityScanResult::query();
        
        // Filter by user's accessible deployments if not admin
        if (!$user->hasRole('admin')) {
            $baseQuery->whereHas('deployment', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            });
        }
        
        $total = $baseQuery->count();
        $critical = $baseQuery->clone()->where('severity', 'critical')->count();
        $high = $baseQuery->clone()->where('severity', 'high')->count();
        $medium = $baseQuery->clone()->where('severity', 'medium')->count();
        $low = $baseQuery->clone()->where('severity', 'low')->count();
        $open = $baseQuery->clone()->where('status', 'open')->count();
        $acknowledged = $baseQuery->clone()->where('status', 'acknowledged')->count();
        $fixed = $baseQuery->clone()->where('status', 'fixed')->count();
        
        return [
            'total' => $total,
            'critical' => $critical,
            'high' => $high,
            'medium' => $medium,
            'low' => $low,
            'open' => $open,
            'acknowledged' => $acknowledged,
            'fixed' => $fixed,
        ];
    }

    /**
     * Show form to edit security policy.
     */
    public function editPolicy(SecurityPolicy $policy)
    {
        $this->authorize('update', $policy);
        
        $projects = \App\Models\Project::all();
        
        return view('security.edit-policy', compact('policy', 'projects'));
    }

    /**
     * Update security policy.
     */
    public function updatePolicy(Request $request, SecurityPolicy $policy)
    {
        $this->authorize('update', $policy);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'project_id' => 'nullable|exists:projects,id',
            'max_critical_vulnerabilities' => 'required|integer|min:0|max:100',
            'max_high_vulnerabilities' => 'required|integer|min:0|max:100',
            'max_medium_vulnerabilities' => 'required|integer|min:0|max:500',
            'max_low_vulnerabilities' => 'required|integer|min:0|max:1000',
            'required_scan_types' => 'required|array|min:1',
            'required_scan_types.*' => 'in:sast,dependency,secrets,infrastructure,container',
            'is_active' => 'nullable|boolean'
        ]);

        try {
            $policy->update([
                'name' => $request->name,
                'description' => $request->description,
                'project_id' => $request->project_id,
                'max_critical_vulnerabilities' => $request->max_critical_vulnerabilities,
                'max_high_vulnerabilities' => $request->max_high_vulnerabilities,
                'max_medium_vulnerabilities' => $request->max_medium_vulnerabilities,
                'max_low_vulnerabilities' => $request->max_low_vulnerabilities,
                'required_scan_types' => $request->required_scan_types,
                'is_active' => $request->boolean('is_active')
            ]);

            return redirect()->route('security.policies')
                ->with('success', 'Security policy updated successfully!');

        } catch (\Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to update security policy: ' . $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Show policy details.
     */
    public function showPolicy(SecurityPolicy $policy)
    {
        $this->authorize('view', $policy);
        
        $policy->load('project');
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $policy->id,
                'name' => $policy->name,
                'description' => $policy->description,
                'project' => $policy->project ? $policy->project->name : 'All Projects',
                'max_critical_vulnerabilities' => $policy->max_critical_vulnerabilities,
                'max_high_vulnerabilities' => $policy->max_high_vulnerabilities,
                'max_medium_vulnerabilities' => $policy->max_medium_vulnerabilities,
                'max_low_vulnerabilities' => $policy->max_low_vulnerabilities,
                'required_scan_types' => $policy->required_scan_types,
                'is_active' => $policy->is_active,
                'created_at' => $policy->created_at->format('M d, Y H:i'),
                'updated_at' => $policy->updated_at->format('M d, Y H:i')
            ]
        ]);
    }

    /**
     * Duplicate security policy.
     */
    public function duplicatePolicy(SecurityPolicy $policy)
    {
        $this->authorize('create', SecurityPolicy::class);

        try {
            $newPolicy = $policy->replicate();
            $newPolicy->name = $policy->name . ' (Copy)';
            $newPolicy->save();

            return response()->json([
                'success' => true,
                'message' => 'Security policy duplicated successfully!',
                'redirect' => route('security.policies')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate security policy: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete security policy.
     */
    public function deletePolicy(SecurityPolicy $policy)
    {
        $this->authorize('delete', $policy);

        try {
            $policy->delete();

            return response()->json([
                'success' => true,
                'message' => 'Security policy deleted successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete security policy: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get policy compliance statistics.
     */
    protected function getPolicyCompliance($user): array
    {
        // Get real policy compliance statistics from database
        $totalPolicies = SecurityPolicy::active()->count();
        
        // Count projects that are compliant (no blocking vulnerabilities in recent deployments)
        $projects = \App\Models\Project::with(['deployments' => function ($query) {
            $query->latest()->limit(1);
        }])->get();
        
        $compliantProjects = 0;
        foreach ($projects as $project) {
            $latestDeployment = $project->deployments->first();
            if ($latestDeployment) {
                $blockingVulns = SecurityScanResult::where('deployment_id', $latestDeployment->id)
                    ->whereIn('severity', ['critical', 'high'])
                    ->where('status', 'open')
                    ->count();
                    
                if ($blockingVulns === 0) {
                    $compliantProjects++;
                }
            }
        }
        
        $complianceRate = $projects->count() > 0 ? ($compliantProjects / $projects->count()) * 100 : 0;
        
        return [
            'total_policies' => $totalPolicies,
            'compliant_projects' => $compliantProjects,
            'compliance_rate' => round($complianceRate, 1)
        ];
    }
}
