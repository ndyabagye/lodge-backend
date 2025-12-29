<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CHECKED_IN = 'checked_in';
    case CHECKED_OUT = 'checked_out';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::CONFIRMED => 'Confirmed',
            self::CHECKED_IN => 'Checked In',
            self::CHECKED_OUT => 'Checked Out',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED, self::CHECKED_IN]);
    }

    public function isCancellable(): bool
    {
        return in_array($this, [self::PENDING, self::CONFIRMED]);
    }
}
