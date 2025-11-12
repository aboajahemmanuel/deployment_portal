<?php

namespace App\Policies;

use App\Models\SecurityPolicy;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SecurityPolicyPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'developer']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SecurityPolicy $securityPolicy): bool
    {
        return $user->hasAnyRole(['admin', 'developer']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SecurityPolicy $securityPolicy): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SecurityPolicy $securityPolicy): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SecurityPolicy $securityPolicy): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SecurityPolicy $securityPolicy): bool
    {
        return $user->hasRole('admin');
    }
}
