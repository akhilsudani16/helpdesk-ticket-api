<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;
use App\Permissions\V1\Abilities;

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
        if (!$user->tokenCan(Abilities::ViewTickets)) {
            return false;
        }

        // Admin can view all tickets
        if ($user->isAdmin()) {
            return true;
        }

        // Agent can view assigned tickets
        if ($user->isAgent() && $ticket->assigned_to === $user->id) {
            return true;
        }

        // Customer can view their own tickets
        return $ticket->user_id === $user->id;
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
        if (!$user->tokenCan(Abilities::UpdateTicket)) {
            return false;
        }

        // Admin can update any ticket
        if ($user->isAdmin() && $user->tokenCan(Abilities::UpdateAnyTicket)) {
            return true;
        }

        // Agent can update assigned tickets
        if ($user->isAgent() && $ticket->assigned_to === $user->id) {
            return true;
        }

        // Customer can update their own tickets
        return $ticket->user_id === $user->id;
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
        if (!$user->tokenCan(Abilities::DeleteTicket)) {
            return false;
        }

        // Admin can delete any ticket
        if ($user->isAdmin() && $user->tokenCan(Abilities::DeleteAnyTicket)) {
            return true;
        }

        // Customer can delete their own ticket only if status is 'open'
        if ($user->isCustomer() && $ticket->user_id === $user->id && $ticket->status->value === 'open') {
            return true;
        }

        return false;
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
