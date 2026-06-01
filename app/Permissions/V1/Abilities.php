<?php

namespace App\Permissions\V1;

use App\Models\User;

final class Abilities
{
    // Ticket abilities
    public const ViewTickets = 'tickets:view';
    public const CreateTicket = 'tickets:create';
    public const UpdateTicket = 'tickets:update';
    public const DeleteTicket = 'tickets:delete';
    public const CreateAnyTicket = 'tickets:create-any';
    public const UpdateAnyTicket = 'tickets:update-any';
    public const DeleteAnyTicket = 'tickets:delete-any';

    // Comment abilities
    public const ViewComments = 'comments:view';
    public const CreateComment = 'comments:create';
    public const CreateInternalComment = 'comments:create-internal';

    // User abilities
    public const ViewUsers = 'users:view';
    public const ManageUsers = 'users:manage';

    /**
     * Get the ability array for a user based on their role.
     *
     * @param User $user
     * @return array<int, string>
     */
    public static function getAbilities(User $user): array
    {
        return match ($user->role->value) {
            'admin' => self::getAdminAbilities(),
            'agent' => self::getAgentAbilities(),
            'customer' => self::getCustomerAbilities(),
            default => [],
        };
    }

    /**
     * Get admin abilities.
     *
     * @return array<int, string>
     */
    public static function getAdminAbilities(): array
    {
        return [
            self::ViewTickets,
            self::CreateTicket,
            self::UpdateTicket,
            self::DeleteTicket,
            self::CreateAnyTicket,
            self::UpdateAnyTicket,
            self::DeleteAnyTicket,
            self::ViewComments,
            self::CreateComment,
            self::CreateInternalComment,
            self::ViewUsers,
            self::ManageUsers,
        ];
    }

    /**
     * Get agent abilities.
     *
     * @return array<int, string>
     */
    public static function getAgentAbilities(): array
    {
        return [
            self::ViewTickets,
            self::UpdateTicket,
            self::ViewComments,
            self::CreateComment,
            self::CreateInternalComment,
            self::ViewUsers,
        ];
    }

    /**
     * Get customer abilities.
     *
     * @return array<int, string>
     */
    public static function getCustomerAbilities(): array
    {
        return [
            self::ViewTickets,
            self::CreateTicket,
            self::UpdateTicket,
            self::DeleteTicket,
            self::ViewComments,
            self::CreateComment,
        ];
    }

    /**
     * Get all available abilities.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::ViewTickets,
            self::CreateTicket,
            self::UpdateTicket,
            self::DeleteTicket,
            self::CreateAnyTicket,
            self::UpdateAnyTicket,
            self::DeleteAnyTicket,
            self::ViewComments,
            self::CreateComment,
            self::CreateInternalComment,
            self::ViewUsers,
            self::ManageUsers,
        ];
    }
}
