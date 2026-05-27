<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine if the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->tokenCan('users:view') && ($user->isAdmin() || $user->isAgent());
    }

    /**
     * Determine if the user can view the user.
     */
    public function view(User $user, User $model): bool
    {
        if (!$user->tokenCan('users:view')) {
            return false;
        }

        // Admin and agents can view users
        return $user->isAdmin() || $user->isAgent();
    }

    /**
     * Determine if the user can manage users.
     */
    public function manage(User $user): bool
    {
        return $user->tokenCan('users:manage') && $user->isAdmin();
    }
}
