<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case User = 'user';

    /**
     * Get all role values as an array.
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if this role has admin privileges.
     */
    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    /**
     * Get a human-readable label for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::User => 'Standard User',
        };
    }
}
