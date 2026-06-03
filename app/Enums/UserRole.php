<?php

namespace App\Enums;

enum UserRole: string
{
    case ADMIN = 'admin';
    case AGENT = 'agent';
    case CUSTOMER = 'customer';

    /**
     * Get all role values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get role display name.
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrator',
            self::AGENT => 'Support Agent',
            self::CUSTOMER => 'Customer',
        };
    }

    /**
     * Check if role is admin.
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * Check if role is agent.
     */
    public function isAgent(): bool
    {
        return $this === self::AGENT;
    }

    /**
     * Check if role is customer.
     */
    public function isCustomer(): bool
    {
        return $this === self::CUSTOMER;
    }
}