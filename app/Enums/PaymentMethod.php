<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CARD = 'card';
    case MOBILE_MONEY = 'mobile_money';
    case BANK_TRANSFER = 'bank_transfer';
    case CASH = 'cash';

    public function label(): string
    {
        return match ($this) {
            self::CARD => 'Credit/Debit Card',
            self::MOBILE_MONEY => 'Mobile Money',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::CASH => 'Cash',
        };
    }
}
