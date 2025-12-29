<?php

namespace App\Enums;

enum AccommodationStatus: string
{
    case AVAILABLE = 'available';
    case MAINTENANCE = 'maintenance';
    case COMING_SOON = 'coming_soon';
    case ARCHIVED = 'archived';

    public function label(): string
    {
        return match($this) {
            self::AVAILABLE => 'Available',
            self::MAINTENANCE => 'Under Maintenance',
            self::COMING_SOON => 'Coming Soon',
            self::ARCHIVED => 'Archived',
        };
    }

    public function isBookable(): bool
    {
        return $this === self::AVAILABLE;
    }
}
