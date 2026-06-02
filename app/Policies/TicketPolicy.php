<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use App\Permissions\V1\Abilities;
use Illuminate\Auth\Access\AuthorizationException;

class TicketPolicy
{
    /**
     * Determine if the user can view any tickets.
     */
    public function viewAny(User $user): bool
    {
        return $user->tokenCan(Abilities::ViewTickets);
    }

    /**
     * Determine if the user can view the ticket.
     */
    public function view(User $user, Ticket $ticket): bool
    {
        // Admin can view all tickets
        if ($user->isAdmin()) {
            return true;
        }

        // Agent can view assigned tickets
        if ($user->isAgent() && $ticket->assigned_to === $user->id) {
            return true;
        }

        // Customer can view their own tickets
        if ($ticket->user_id === $user->id) {
            return true;
        }

        throw new AuthorizationException(
            __('validation.ticket_view_permission_denied')
        );
    }

    /**
     * Determine if the user can create tickets.
     */
    public function create(User $user): bool
    {
        return $user->tokenCan(Abilities::CreateTicket);
    }

    /**
     * Determine if the user can create tickets for any user.
     */
    public function createAny(User $user): bool
    {
        return $user->tokenCan(Abilities::CreateAnyTicket);
    }

    /**
     * Determine if the user can update the ticket.
     */
    public function update(User $user, Ticket $ticket): bool
    {
        // Admin can update any ticket
        if ($user->isAdmin() && $user->tokenCan(Abilities::UpdateAnyTicket)) {
            return true;
        }

        // Agent can update assigned tickets
        if ($user->isAgent() && $ticket->assigned_to === $user->id) {
            return true;
        }

        // Customer can update their own tickets
        if ($ticket->user_id === $user->id) {
            return true;
        }

        throw new AuthorizationException(
            __('validation.ticket_update_permission_denied')
        );
    }

    /**
     * Determine if the user can update any ticket.
     */
    public function updateAny(User $user): bool
    {
        return $user->tokenCan(Abilities::UpdateAnyTicket);
    }

    /**
     * Determine if the user can delete the ticket.
     */
    public function delete(User $user, Ticket $ticket): bool
    {
        // Admin can delete any ticket
        if ($user->isAdmin() && $user->tokenCan(Abilities::DeleteAnyTicket)) {
            return true;
        }

        // Customer can delete their own ticket only if the status is 'open'
        if ($user->isCustomer() && $ticket->user_id === $user->id) {
            if ($ticket->status->value === 'open') {
                return true;
            }

            throw new AuthorizationException(
                __('validation.ticket_only_open_delete_permission') . $ticket->status->value . '.'
            );
        }

        throw new AuthorizationException(
            __('validation.ticket_delete_permission_denied')
        );
    }

    /**
     * Determine if the user can restore the ticket.
     */
    public function restore(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() && $user->tokenCan(Abilities::DeleteAnyTicket);
    }

    /**
     * Determine if the user can permanently delete the ticket.
     */
    public function forceDelete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() && $user->tokenCan(Abilities::DeleteAnyTicket);
    }
}
