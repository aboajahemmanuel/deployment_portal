<?php

namespace App\Policies;

use App\Models\ScheduledDeployment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ScheduledDeploymentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->roles->contains('name', 'admin') || $user->roles->contains('name', 'developer');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ScheduledDeployment $scheduledDeployment): bool
    {
        // Admins can view all scheduled deployments
        if ($user->roles->contains('name', 'admin')) {
            return true;
        }

        // Developers can only view their own scheduled deployments
        if ($user->roles->contains('name', 'developer')) {
            return $scheduledDeployment->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->roles->contains('name', 'admin') || $user->roles->contains('name', 'developer');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ScheduledDeployment $scheduledDeployment): bool
    {
        // Admins can update all scheduled deployments
        if ($user->roles->contains('name', 'admin')) {
            return true;
        }

        // Developers can only update their own scheduled deployments
        if ($user->roles->contains('name', 'developer')) {
            return $scheduledDeployment->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ScheduledDeployment $scheduledDeployment): bool
    {
        // Admins can delete all scheduled deployments
        if ($user->roles->contains('name', 'admin')) {
            return true;
        }

        // Developers can only delete their own scheduled deployments
        if ($user->roles->contains('name', 'developer')) {
            return $scheduledDeployment->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ScheduledDeployment $scheduledDeployment): bool
    {
        return $this->update($user, $scheduledDeployment);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ScheduledDeployment $scheduledDeployment): bool
    {
        return $user->roles->contains('name', 'admin');
    }
}
