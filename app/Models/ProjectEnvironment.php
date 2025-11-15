<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectEnvironment extends Model
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
        'deploy_endpoint',
        'rollback_endpoint',
        'application_url',
        'project_path',
        'env_variables',
        'branch',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the project that owns this environment configuration.
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the environment for this configuration.
     */
    public function environment()
    {
        return $this->belongsTo(Environment::class);
    }

    /**
     * Get the deployments for this project environment.
     */
    public function deployments()
    {
        return $this->hasMany(Deployment::class, 'environment_id', 'environment_id')
            ->where('project_id', $this->project_id);
    }

    /**
     * Scope for active project environments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
