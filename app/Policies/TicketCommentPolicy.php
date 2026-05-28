<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;

class TicketCommentPolicy
{
    /**
     * Determine if the user can view any comments.
     */
    public function viewAny(User $user, Ticket $ticket): bool
    {
        return $user->tokenCan('comments:view') && $user->can('view', $ticket);
    }

    /**
     * Determine if the user can view the comment.
     */
    public function view(User $user, TicketComment $comment): bool
    {
        if (!$user->tokenCan('comments:view')) {
            return false;
        }

        if ($user->isAdmin() || $user->isAgent()) {
            return true;
        }

        // Customers can only see public comments
        return !$comment->is_internal;
    }

    /**
     * Determine if the user can create comments.
     */
    public function create(User $user, Ticket $ticket): bool
    {
        return $user->tokenCan('comments:create') && $user->can('view', $ticket);
    }

    /**
     * Determine if the user can create internal comments.
     */
    public function createInternal(User $user): bool
    {
        return $user->tokenCan('comments:create-internal') &&
               ($user->isAdmin() || $user->isAgent());
    }
}
