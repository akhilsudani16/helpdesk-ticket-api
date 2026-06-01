<?php

namespace App\Providers;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use App\Permissions\V1\Abilities;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure rate limiters (Moved from app.php)
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Register Gates
        $this->registerTicketGates();
        $this->registerCommentGates();
        $this->registerUserGates();
    }

    /**
     * Register ticket-related gates.
     */
    protected function registerTicketGates(): void
    {
        // View any tickets
        Gate::define('tickets.viewAny', function (User $user) {
            return $user->tokenCan(Abilities::ViewTickets);
        });

        // View specific ticket
        Gate::define('tickets.view', function (User $user, Ticket $ticket) {
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
        });

        // Create ticket
        Gate::define('tickets.create', function (User $user) {
            return $user->tokenCan(Abilities::CreateTicket);
        });

        // Create ticket for any user
        Gate::define('tickets.createAny', function (User $user) {
            return $user->tokenCan(Abilities::CreateAnyTicket);
        });

        // Update ticket
        Gate::define('tickets.update', function (User $user, Ticket $ticket) {
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
        });

        // Update any ticket
        Gate::define('tickets.updateAny', function (User $user) {
            return $user->tokenCan(Abilities::UpdateAnyTicket);
        });

        // Delete ticket
        Gate::define('tickets.delete', function (User $user, Ticket $ticket) {
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
        });

        // Restore ticket
        Gate::define('tickets.restore', function (User $user, Ticket $ticket) {
            return $user->isAdmin() && $user->tokenCan(Abilities::DeleteAnyTicket);
        });

        // Force delete ticket
        Gate::define('tickets.forceDelete', function (User $user, Ticket $ticket) {
            return $user->isAdmin() && $user->tokenCan(Abilities::DeleteAnyTicket);
        });
    }

    /**
     * Register comment-related gates.
     */
    protected function registerCommentGates(): void
    {
        // View any comments
        Gate::define('comments.viewAny', function (User $user, Ticket $ticket) {
            return $user->tokenCan(Abilities::ViewComments) && Gate::allows('tickets.view', $ticket);
        });

        // View specific comment
        Gate::define('comments.view', function (User $user, TicketComment $comment) {
            if (!$user->tokenCan(Abilities::ViewComments)) {
                return false;
            }

            if ($user->isAdmin() || $user->isAgent()) {
                return true;
            }

            return !$comment->is_internal;
        });

        // Create comment
        Gate::define('comments.create', function (User $user, Ticket $ticket) {
            return $user->tokenCan(Abilities::CreateComment) && Gate::allows('tickets.view', $ticket);
        });

        // Create internal comment
        Gate::define('comments.createInternal', function (User $user) {
            return $user->tokenCan(Abilities::CreateInternalComment) &&
                   ($user->isAdmin() || $user->isAgent());
        });

        // Delete comment
        Gate::define('comments.delete', function (User $user, TicketComment $comment) {
            if ($user->isAdmin()) {
                return true;
            }

            if ($user->isAgent() && $comment->ticket?->assigned_to === $user->id) {
                return true;
            }

            return $comment->user_id === $user->id;
        });
    }

    /**
     * Register user-related gates.
     */
    protected function registerUserGates(): void
    {
        // View any users
        Gate::define('users.viewAny', function (User $user) {
            return $user->tokenCan(Abilities::ViewUsers) && 
                   ($user->isAdmin() || $user->isAgent());
        });

        // View specific user
        Gate::define('users.view', function (User $user, User $targetUser) {
            if (!$user->tokenCan(Abilities::ViewUsers)) {
                return false;
            }

            // Admin and agents can view users
            return $user->isAdmin() || $user->isAgent();
        });

        // Manage users
        Gate::define('users.manage', function (User $user) {
            return $user->tokenCan(Abilities::ManageUsers) && $user->isAdmin();
        });
    }
}