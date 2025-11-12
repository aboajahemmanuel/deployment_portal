<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PipelineStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'deployment_id',
        'name',
        'display_name',
        'description',
        'order',
        'status',
        'started_at',
        'completed_at',
        'duration',
        'output',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Get the deployment that owns this pipeline stage.
     */
    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

    /**
     * Get the duration in human readable format.
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration) {
            return 'N/A';
        }

        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$seconds}s";
        }

        return "{$seconds}s";
    }

    /**
     * Get the status color for UI display.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'success' => 'success',
            'failed' => 'danger',
            'running' => 'warning',
            'pending' => 'secondary',
            'skipped' => 'info',
            default => 'secondary'
        };
    }

    /**
     * Get the status icon for UI display.
     */
    public function getStatusIconAttribute(): string
    {
        return match ($this->status) {
            'success' => 'ni-check-circle',
            'failed' => 'ni-cross-circle',
            'running' => 'ni-loader',
            'pending' => 'ni-clock',
            'skipped' => 'ni-forward-ios',
            default => 'ni-help'
        };
    }

    /**
     * Check if the stage is completed (success or failed).
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['success', 'failed', 'skipped']);
    }

    /**
     * Check if the stage is currently running.
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if the stage is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Mark the stage as started.
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark the stage as completed with success.
     */
    public function markAsSuccess(string $output = null): void
    {
        $completedAt = now();
        $duration = $this->started_at ? $completedAt->diffInSeconds($this->started_at) : null;

        $this->update([
            'status' => 'success',
            'completed_at' => $completedAt,
            'duration' => $duration,
            'output' => $output,
        ]);
    }

    /**
     * Mark the stage as failed.
     */
    public function markAsFailed(string $errorMessage = null, string $output = null): void
    {
        $completedAt = now();
        $duration = $this->started_at ? $completedAt->diffInSeconds($this->started_at) : null;

        $this->update([
            'status' => 'failed',
            'completed_at' => $completedAt,
            'duration' => $duration,
            'error_message' => $errorMessage,
            'output' => $output,
        ]);
    }

    /**
     * Mark the stage as skipped.
     */
    public function markAsSkipped(string $reason = null): void
    {
        $this->update([
            'status' => 'skipped',
            'completed_at' => now(),
            'error_message' => $reason,
        ]);
    }

    /**
     * Scope to get stages ordered by their pipeline order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Scope to get stages by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
