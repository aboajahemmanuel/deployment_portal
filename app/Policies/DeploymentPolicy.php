<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Deployment;
use Illuminate\Auth\Access\Response;

class DeploymentPolicy
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
    public function view(User $user, Deployment $deployment): bool
    {
        return $user->hasRole('admin') || $user->hasRole('developer');
    }
}
