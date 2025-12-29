<?php

namespace App\Enums;

enum UserRole: string
{
    case GUEST = 'guest';
    case STAFF = 'staff';
    case ADMIN = 'admin';

    public function label(): string
    {
        return match($this) {
            self::GUEST => 'Guest',
            self::STAFF => 'Staff',
            self::ADMIN => 'Administrator',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    public function isStaff(): bool
    {
        return $this === self::STAFF || $this === self::ADMIN;
    }
}
