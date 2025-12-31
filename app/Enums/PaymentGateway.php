<?php

namespace App\Enums;

enum PaymentGateway: string
{
    case STRIPE = 'stripe';
    case FLUTTERWAVE = 'flutterwave';
    case PESAPAL = 'pesapal';
    case IOTEC = 'iotec';

    public function label(): string
    {
        return match($this) {
            self::STRIPE => 'Stripe',
            self::FLUTTERWAVE => 'Flutterwave',
            self::PESAPAL => 'Pesapal',
            self::IOTEC => 'IoTec',
        };
    }

    public function supportsMethod(PaymentMethod $method): bool
    {
        return match($this) {
            self::STRIPE => in_array($method, [PaymentMethod::CARD]),
            self::FLUTTERWAVE => in_array($method, [PaymentMethod::CARD, PaymentMethod::MOBILE_MONEY]),
            self::PESAPAL => in_array($method, [PaymentMethod::CARD, PaymentMethod::MOBILE_MONEY, PaymentMethod::BANK_TRANSFER]),
            self::IOTEC => in_array($method, [PaymentMethod::CARD, PaymentMethod::MOBILE_MONEY]),
        };
    }
}
