<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityScanResult extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'deployment_id',
        'pipeline_stage_id',
        'scan_type',
        'tool_name',
        'severity',
        'vulnerability_id',
        'cve_id',
        'title',
        'description',
        'file_path',
        'line_number',
        'code_snippet',
        'remediation_advice',
        'reference_url',
        'status',
        'metadata',
        'first_detected_at',
        'last_seen_at',
        'acknowledged_by',
        'acknowledged_at',
        'acknowledgment_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'first_detected_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'line_number' => 'integer',
    ];

    /**
     * Get the deployment that owns the security scan result.
     */
    public function deployment()
    {
        return $this->belongsTo(Deployment::class);
    }

    /**
     * Get the pipeline stage that owns the security scan result.
     */
    public function pipelineStage()
    {
        return $this->belongsTo(PipelineStage::class);
    }

    /**
     * Get the user who acknowledged this result.
     */
    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * Scope a query to only include critical vulnerabilities.
     */
    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    /**
     * Scope a query to only include high severity vulnerabilities.
     */
    public function scopeHigh($query)
    {
        return $query->where('severity', 'high');
    }

    /**
     * Scope a query to only include open vulnerabilities.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    /**
     * Scope a query to filter by scan type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('scan_type', $type);
    }

    /**
     * Get the severity color for UI display.
     */
    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'critical' => 'danger',
            'high' => 'warning',
            'medium' => 'info',
            'low' => 'secondary',
            'info' => 'light',
            default => 'secondary'
        };
    }

    /**
     * Get the severity icon for UI display.
     */
    public function getSeverityIconAttribute(): string
    {
        return match($this->severity) {
            'critical' => 'ni ni-alert-circle-fill',
            'high' => 'ni ni-alert-triangle-fill',
            'medium' => 'ni ni-info-fill',
            'low' => 'ni ni-minus-circle',
            'info' => 'ni ni-help-fill',
            default => 'ni ni-help'
        };
    }

    /**
     * Check if the vulnerability is blocking (critical or high).
     */
    public function isBlocking(): bool
    {
        return in_array($this->severity, ['critical', 'high']);
    }

    /**
     * Mark the vulnerability as acknowledged.
     */
    public function acknowledge(User $user, string $reason = null): bool
    {
        return $this->update([
            'status' => 'acknowledged',
            'acknowledged_by' => $user->id,
            'acknowledged_at' => now(),
            'acknowledgment_reason' => $reason,
        ]);
    }

    /**
     * Mark the vulnerability as false positive.
     */
    public function markAsFalsePositive(User $user, string $reason = null): bool
    {
        return $this->update([
            'status' => 'false_positive',
            'acknowledged_by' => $user->id,
            'acknowledged_at' => now(),
            'acknowledgment_reason' => $reason,
        ]);
    }

    /**
     * Mark the vulnerability as fixed.
     */
    public function markAsFixed(): bool
    {
        return $this->update([
            'status' => 'fixed',
            'last_seen_at' => now(),
        ]);
    }
}
