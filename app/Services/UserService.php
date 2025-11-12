<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserService
{
    /**
     * Update user information
     *
     * @param User $user
     * @param array $data
     * @return User
     */
   public function updateUser(User $user, array $data)
{
    $updateData = [
        'name' => $data['name'],
        'email' => $data['email'],
        'phone' => $data['phone'] ?? null,
        'website' => $data['website'] ?? null,
        'bio' => $data['bio'] ?? null,
        'email_verified_at' => isset($data['email_verified']) && $data['email_verified']
            ? ($user->email_verified_at ?? now())
            : $user->email_verified_at,
    ];

    $user->update($updateData);

    if (array_key_exists('roles', $data)) {
        $user->syncRoles($data['roles'] ?? []);
    }

    return $user;
}


    /**
     * Create a new user
     *
     * @param array $data
     * @return User
     */
    public function createUser(array $data)
    {
        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'] ?? null,
            'bio' => $data['bio'] ?? null,
            'website' => $data['website'] ?? null,
            'email_verified_at' => now(), // Admin created users are auto-verified
        ];

        $user = User::create($userData);
        $user->assignRole($data['role']);

        return $user;
    }

    /**
     * Reset user password
     *
     * @param User $user
     * @param string $password
     * @return User
     */
    public function resetPassword(User $user, string $password)
    {
        $user->update([
            'password' => Hash::make($password),
        ]);

        return $user;
    }

    /**
     * Promote user to author
     *
     * @param User $user
     * @return User
     */
    public function promoteToAuthor(User $user)
    {
        if (!$user->hasRole('author')) {
            $user->assignRole('author');
            $user->update(['promoted_to_author_at' => now()]);
        }

        return $user;
    }

    /**
     * Get all roles
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllRoles()
    {
        return Role::all();
    }
}