<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Project;
use Illuminate\Auth\Access\Response;

class ProjectPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('developer');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Project $project): bool
    {
        // Admins can view all projects, developers can only view their own
        if ($user->hasRole('admin')) {
            return true;
        }
        
        if ($user->hasRole('developer')) {
            return $project->user_id === $user->id;
        }
        
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('developer');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Project $project): bool
    {
        // Admins can update all projects, developers can only update their own
        if ($user->hasRole('admin')) {
            return true;
        }
        
        if ($user->hasRole('developer')) {
            return $project->user_id === $user->id;
        }
        
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Project $project): bool
    {
        // Only admins can delete projects
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can deploy the project.
     */
    public function deploy(User $user, Project $project): bool
    {
        // Admins can deploy all projects, developers can only deploy their own
        if ($user->hasRole('admin')) {
            return true;
        }
        
        if ($user->hasRole('developer')) {
            return $project->user_id === $user->id;
        }
        
        return false;
    }
}