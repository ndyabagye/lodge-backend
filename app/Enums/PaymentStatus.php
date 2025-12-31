<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case PARTIALLY_PAID = 'partially_paid';
    case REFUNDED = 'refunded';
    case FAILED = 'failed';
    case COMPLETED = 'completed';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PAID => 'Paid',
            self::PARTIALLY_PAID => 'Partially Paid',
            self::REFUNDED => 'Refunded',
            self::FAILED => 'Failed',
            self::COMPLETED => 'Completed',
        };
    }

    public function isPaid(): bool
    {
        return $this === self::PAID;
    }

    public function isPartiallyPaid(): bool
    {
        return $this === self::PARTIALLY_PAID;
    }

    public function needsPayment(): bool
    {
        return in_array($this, [self::PENDING, self::PARTIALLY_PAID, self::FAILED]);
    }

    public function isRefunded(): bool
    {
        return $this === self::REFUNDED;
    }

    public function isCompleted(): bool
    {
        return $this === self::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this === self::FAILED;
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }
}
