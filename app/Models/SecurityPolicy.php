<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityPolicy extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'name',
        'description',
        'is_active',
        'max_critical_vulnerabilities',
        'max_high_vulnerabilities',
        'max_medium_vulnerabilities',
        'max_low_vulnerabilities',
        'required_scan_types',
        'block_on_secrets',
        'block_on_license_violations',
        'allowed_licenses',
        'blocked_licenses',
        'scan_timeout_minutes',
        'max_retry_attempts',
        'environment_overrides',
        'notify_on_failure',
        'notify_on_new_vulnerabilities',
        'notification_channels',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'required_scan_types' => 'array',
        'block_on_secrets' => 'boolean',
        'block_on_license_violations' => 'boolean',
        'allowed_licenses' => 'array',
        'blocked_licenses' => 'array',
        'environment_overrides' => 'array',
        'notify_on_failure' => 'boolean',
        'notify_on_new_vulnerabilities' => 'boolean',
        'notification_channels' => 'array',
        'max_critical_vulnerabilities' => 'integer',
        'max_high_vulnerabilities' => 'integer',
        'max_medium_vulnerabilities' => 'integer',
        'max_low_vulnerabilities' => 'integer',
        'scan_timeout_minutes' => 'integer',
        'max_retry_attempts' => 'integer',
    ];

    /**
     * Get the project that owns the security policy.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Scope a query to only include active policies.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the default security policy configuration.
     */
    public static function getDefaultPolicy(): array
    {
        return [
            'name' => 'Default Security Policy',
            'description' => 'Standard security policy for all projects',
            'is_active' => true,
            'max_critical_vulnerabilities' => 0,
            'max_high_vulnerabilities' => 0,
            'max_medium_vulnerabilities' => 10,
            'max_low_vulnerabilities' => 50,
            'required_scan_types' => ['sast', 'dependency', 'secrets'],
            'block_on_secrets' => true,
            'block_on_license_violations' => false,
            'allowed_licenses' => ['MIT', 'Apache-2.0', 'BSD-3-Clause', 'ISC'],
            'blocked_licenses' => ['GPL-3.0', 'AGPL-3.0'],
            'scan_timeout_minutes' => 30,
            'max_retry_attempts' => 3,
            'notify_on_failure' => true,
            'notify_on_new_vulnerabilities' => true,
            'notification_channels' => ['email'],
        ];
    }

    /**
     * Check if the policy allows deployment based on scan results.
     */
    public function allowsDeployment(array $vulnerabilityCounts): bool
    {
        $critical = $vulnerabilityCounts['critical'] ?? 0;
        $high = $vulnerabilityCounts['high'] ?? 0;
        $medium = $vulnerabilityCounts['medium'] ?? 0;
        $low = $vulnerabilityCounts['low'] ?? 0;

        return $critical <= $this->max_critical_vulnerabilities &&
               $high <= $this->max_high_vulnerabilities &&
               $medium <= $this->max_medium_vulnerabilities &&
               $low <= $this->max_low_vulnerabilities;
    }

    /**
     * Get policy for specific environment with overrides applied.
     */
    public function getEnvironmentPolicy(string $environment = 'production'): array
    {
        $basePolicy = $this->toArray();
        $overrides = $this->environment_overrides[$environment] ?? [];
        
        return array_merge($basePolicy, $overrides);
    }

    /**
     * Check if a scan type is required by this policy.
     */
    public function requiresScanType(string $scanType): bool
    {
        return in_array($scanType, $this->required_scan_types);
    }

    /**
     * Get the violation message for failed policy check.
     */
    public function getViolationMessage(array $vulnerabilityCounts): string
    {
        $violations = [];
        
        if (($vulnerabilityCounts['critical'] ?? 0) > $this->max_critical_vulnerabilities) {
            $violations[] = "Critical: {$vulnerabilityCounts['critical']} found, {$this->max_critical_vulnerabilities} allowed";
        }
        
        if (($vulnerabilityCounts['high'] ?? 0) > $this->max_high_vulnerabilities) {
            $violations[] = "High: {$vulnerabilityCounts['high']} found, {$this->max_high_vulnerabilities} allowed";
        }
        
        if (($vulnerabilityCounts['medium'] ?? 0) > $this->max_medium_vulnerabilities) {
            $violations[] = "Medium: {$vulnerabilityCounts['medium']} found, {$this->max_medium_vulnerabilities} allowed";
        }
        
        if (($vulnerabilityCounts['low'] ?? 0) > $this->max_low_vulnerabilities) {
            $violations[] = "Low: {$vulnerabilityCounts['low']} found, {$this->max_low_vulnerabilities} allowed";
        }
        
        return 'Security policy violations: ' . implode('; ', $violations);
    }
}
