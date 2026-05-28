<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    /**
     * Determine if the user can view any tickets.
     */
    public function viewAny(User $user): bool
    {
        return $user->tokenCan('tickets:view');
    }

    /**
     * Determine if the user can view the ticket.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        if (!$user->tokenCan('tickets:view')) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgent() && $ticket->assigned_to === $user->id) {
            return true;
        }

        return $ticket->user_id === $user->id;
    }

    /**
     * Determine if the user can create tickets.
     */
    public function create(User $user): bool
    {
        return $user->tokenCan('tickets:create');
    }

    /**
     * Determine if the user can create tickets for any user.
     */
    public function createAny(User $user): bool
    {
        return $user->tokenCan('tickets:create-any');
    }

    /**
     * Determine if the user can update the ticket.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        if (!$user->tokenCan('tickets:update')) {
            return false;
        }

        if ($user->isAdmin() && $user->tokenCan('tickets:update-any')) {
            return true;
        }

        if ($user->isAgent() && $ticket->assigned_to === $user->id) {
            return true;
        }

        return $ticket->user_id === $user->id;
    }

    /**
     * Determine if the user can update any ticket.
     */
    public function updateAny(User $user): bool
    {
        return $user->tokenCan('tickets:update-any');
    }

    /**
     * Determine if the user can delete the ticket.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        if (!$user->tokenCan('tickets:delete')) {
            return false;
        }

        if ($user->isAdmin() && $user->tokenCan('tickets:delete-any')) {
            return true;
        }

        if ($user->isCustomer() && $ticket->user_id === $user->id && $ticket->status === 'open') {
            return true;
        }

        return false;
    }

    /**
     * Determine if the user can restore the ticket.
     */
    public function restore(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() && $user->tokenCan('tickets:delete-any');
    }

    /**
     * Determine if the user can permanently delete the ticket.
     */
    public function forceDelete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() && $user->tokenCan('tickets:delete-any');
    }
}
