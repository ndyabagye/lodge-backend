<?php

namespace App\Exceptions;

use Exception;

class PaymentException extends Exception
{
    public static function failed(string $message = 'Payment failed'): self
    {
        return new self($message, 422);
    }

    public static function invalidAmount(string $message = 'Invalid payment amount'): self
    {
        return new self($message, 422);
    }

    public static function gatewayError(string $message): self
    {
        return new self("Payment gateway error: {$message}", 500);
    }

    public static function alreadyPaid(string $message = 'This booking has already been paid'): self
    {
        return new self($message, 422);
    }
}
