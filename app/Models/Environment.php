<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Environment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'server_base_path',
        'server_unc_path',
        'web_base_url',
        'deploy_endpoint_base',
        'description',
        'is_active',
        'order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Get the project environments for this environment.
     */
    public function projectEnvironments()
    {
        return $this->hasMany(ProjectEnvironment::class);
    }

    /**
     * Get the projects that use this environment.
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_environments')
            ->withPivot(['deploy_endpoint', 'rollback_endpoint', 'application_url', 'project_path', 'env_variables', 'branch', 'is_active'])
            ->withTimestamps();
    }

    /**
     * Get the deployments for this environment.
     */
    public function deployments()
    {
        return $this->hasMany(Deployment::class);
    }

    /**
     * Scope for active environments.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for ordering environments.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }
}
