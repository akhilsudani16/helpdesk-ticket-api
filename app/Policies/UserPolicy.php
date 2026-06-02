<?php

namespace App\Policies;

use App\Models\User;
use App\Permissions\V1\Abilities;
use Illuminate\Auth\Access\AuthorizationException;

class UserPolicy
{
    /**
     * Determine if the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        if ($user->tokenCan(Abilities::ViewUsers) && ($user->isAdmin() || $user->isAgent())) {
            return true;
        }

        throw new AuthorizationException(
            __('validation.user_view_permission_denied')
        );
    }

    /**
     * Determine if the user can view the user.
     */
    public function view(User $user, User $model): bool
    {
        // Admin and agents can view users
        if ($user->isAdmin() || $user->isAgent()) {
            return true;
        }

        if($user->isCustomer() )

        throw new AuthorizationException(
            __('validation.user_view_permission_denied')
        );
    }

    /**
     * Determine if the user can manage users.
     */
    public function manage(User $user): bool
    {
        return $user->tokenCan(Abilities::ManageUsers) && $user->isAdmin();
    }
}
