<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Deployment;

class Project extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'repository_url',
        'deploy_endpoint',
        'rollback_endpoint',
        'access_token',
        'current_branch',
        'description',
        'is_active',
        'user_id',
        'project_type',
        'env_variables',
        'application_url',
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
     * Get the deployments for the project.
     */
    public function deployments()
    {
        return $this->hasMany(Deployment::class);
    }

    /**
     * Get the latest deployment for the project.
     */
    public function latestDeployment()
    {
        return $this->hasOne(Deployment::class)->latestOfMany();
    }

    /**
     * Get the user who owns this project.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for filtering projects by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for active projects
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the security policy for this project.
     */
    public function securityPolicy()
    {
        return $this->hasOne(SecurityPolicy::class)->where('is_active', true);
    }
}