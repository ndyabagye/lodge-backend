<?php

namespace App\Enums;

enum ActivityStatus: string
{
    case AVAILABLE = 'available';
    case UNAVAILABLE = 'unavailable';
    case COMING_SOON = 'coming_soon';

    public function label(): string
    {
        return match($this) {
            self::AVAILABLE => 'Available',
            self::UNAVAILABLE => 'Unavailable',
            self::COMING_SOON => 'Coming Soon',
        };
    }

    public function isBookable(): bool
    {
        return $this === self::AVAILABLE;
    }
}
