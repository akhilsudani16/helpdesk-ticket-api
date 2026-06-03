<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Permissions\V1\Abilities;

class TicketCommentPolicy
{
    /**
     * Determine if the user can view any comments.
     */
    public function viewAny(User $user, Ticket $ticket): bool
    {
        // User must be able to view the ticket to view its comments
        return $user->tokenCan(Abilities::ViewComments) && $user->can('view', $ticket);
    }

    /**
     * Determine if the user can view the comment.
     */
    public function view(User $user, TicketComment $comment): bool
    {
        // Admin and agents can see all comments
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
        // User must be able to view the ticket to comment on it
        return $user->tokenCan(Abilities::CreateComment) && $user->can('view', $ticket);
    }

    /**
     * Determine if the user can create internal comments.
     */
    public function createInternal(User $user): bool
    {
        return $user->tokenCan(Abilities::CreateInternalComment) &&
               ($user->isAdmin() || $user->isAgent());
    }

    /**
     * Determine if the user can delete the comment.
     */
    public function delete(User $user, TicketComment $comment): bool
    {
        // Admin can delete any comment
        if ($user->isAdmin()) {
            return true;
        }

        // Agent can delete comments on tickets they're assigned to
        if ($user->isAgent() && $comment->ticket?->assigned_to === $user->id) {
            return true;
        }

        // User can delete their own comments
        return $comment->user_id === $user->id;
    }
}
