<?php

namespace App\Support;

class UserRole
{
    public const ADMIN = 'admin';

    public const STAFF = 'staff';

    public const AGENCY = 'agency';

    /**
     * @return array<string>
     */
    public static function all(): array
    {
        return [
            self::ADMIN,
            self::STAFF,
            self::AGENCY,
        ];
    }

    public static function isValid(string $role): bool
    {
        return in_array($role, self::all(), true);
    }
}
