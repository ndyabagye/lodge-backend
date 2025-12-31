<?php

namespace App\Exceptions;

use Exception;

class BookingException extends Exception
{
    public static function unavailable(string $message = 'Accommodation is not available for the selected dates'): self
    {
        return new self($message, 422);
    }

    public static function notCancellable(string $message = 'This booking cannot be cancelled'): self
    {
        return new self($message, 422);
    }

    public static function notModifiable(string $message = 'This booking cannot be modified'): self
    {
        return new self($message, 422);
    }

    public static function invalidDates(string $message = 'Invalid check-in or check-out dates'): self
    {
        return new self($message, 422);
    }

    public static function exceedsCapacity(int $maxGuests): self
    {
        return new self("Maximum {$maxGuests} guests allowed", 422);
    }

    public static function minimumStayNotMet(int $minimumStay): self
    {
        return new self("Minimum stay of {$minimumStay} night(s) required", 422);
    }
}
