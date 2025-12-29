<?php

namespace App\Enums;

enum BlockedDateReason: string
{
    case MAINTENANCE = 'maintenance';
    case PRIVATE_BOOKING = 'private_booking';
    case OTHER = 'other';

    public function label(): string
    {
        return match($this) {
            self::MAINTENANCE => 'Maintenance',
            self::PRIVATE_BOOKING => 'Private Booking',
            self::OTHER => 'Other',
        };
    }
}
