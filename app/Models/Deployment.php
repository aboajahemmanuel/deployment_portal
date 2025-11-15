<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deployment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'project_id',
        'environment_id',
        'user_id',
        'status',
        'log_output',
        'started_at',
        'completed_at',
        'commit_hash',
        'is_rollback',
        'rollback_target_id',
        'rollback_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'is_rollback' => 'boolean',
    ];

    /**
     * Get the project that owns the deployment.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the user that owns the Deployment
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the pipeline stages for this deployment.
     */
    public function pipelineStages()
    {
        return $this->hasMany(PipelineStage::class)->ordered();
    }

    /**
     * Get the current active pipeline stage.
     */
    public function currentStage()
    {
        return $this->pipelineStages()->where('status', 'running')->first() 
            ?? $this->pipelineStages()->where('status', 'pending')->first();
    }

    /**
     * Get pipeline progress percentage.
     */
    public function getPipelineProgressAttribute(): int
    {
        $totalStages = $this->pipelineStages()->count();
        if ($totalStages === 0) return 0;

        $completedStages = $this->pipelineStages()->whereIn('status', ['success', 'failed', 'skipped'])->count();
        return round(($completedStages / $totalStages) * 100);
    }

    /**
     * Check if deployment has pipeline stages.
     */
    public function hasPipelineStages(): bool
    {
        return $this->pipelineStages()->exists();
    }

    /**
     * Get the logs for this deployment.
     */
    public function logs()
    {
        return $this->hasMany(DeploymentLog::class)->orderBy('created_at');
    }

    /**
     * Get the deployment that this is a rollback of.
     */
    public function rollbackTarget()
    {
        return $this->belongsTo(Deployment::class, 'rollback_target_id');
    }

    /**
     * Get the rollbacks of this deployment.
     */
    public function rollbacks()
    {
        return $this->hasMany(Deployment::class, 'rollback_target_id');
    }

    /**
     * Get the security scan results for this deployment.
     */
    public function securityScanResults()
    {
        return $this->hasMany(SecurityScanResult::class);
    }

    /**
     * Get the environment for this deployment.
     */
    public function environment()
    {
        return $this->belongsTo(Environment::class);
    }
}