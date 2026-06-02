<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Permissions\V1\Abilities;
use Illuminate\Auth\Access\AuthorizationException;

class TicketCommentPolicy
{
    /**
     * Determine if the user can view any comments.
     */
    public function viewAny(User $user, Ticket $ticket): bool
    {
        // User must be able to view the ticket to view its comments
        if ($user->tokenCan(Abilities::ViewComments) && $user->can('view', $ticket)) {
            return true;
        }

        throw new AuthorizationException(
            __('validation.ticket_view_comment_permission_denied')
        );
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
        if (!$comment->is_internal) {
            return true;
        }

        throw new AuthorizationException(
            __('validation.internal_comment_permission_denied')
        );
    }

    /**
     * Determine if the user can create comments.
     */
    public function create(User $user, Ticket $ticket): bool
    {
        // User must be able to view the ticket to comment on it
        if ($user->tokenCan(Abilities::CreateComment) && $user->can('view', $ticket)) {
            return true;
        }

        throw new AuthorizationException(
            __('validation.ticket_comment_create_permission_denied')
        );
    }

    /**
     * Determine if the user can create internal comments.
     */
    public function createInternal(User $user): bool
    {
        if ($user->tokenCan(Abilities::CreateInternalComment) && ($user->isAdmin() || $user->isAgent())) {
            return true;
        }

        throw new AuthorizationException(
            __('validation.internal_comment_create_permission_denied')
        );
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

        // Users can delete their own comments
        if ($comment->user_id === $user->id) {
            return true;
        }

        throw new AuthorizationException(
            __('validation.comment_delete_permission_denied')
        );
    }
}
