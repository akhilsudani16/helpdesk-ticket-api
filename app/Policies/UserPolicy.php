<?php

namespace App\Policies;

use App\Models\User;
use App\Permissions\V1\Abilities;

class UserPolicy
{
    /**
     * Determine if the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->tokenCan(Abilities::ViewUsers) && ($user->isAdmin() || $user->isAgent());
    }

    /**
     * Determine if the user can view the user.
     */
    public function view(User $user, User $model): bool
    {
        // Admin and agents can view users
        return $user->isAdmin() || $user->isAgent();
    }

    /**
     * Determine if the user can manage users.
     */
    public function manage(User $user): bool
    {
        return $user->tokenCan(Abilities::ManageUsers) && $user->isAdmin();
    }
}
