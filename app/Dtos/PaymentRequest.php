<?php

namespace App\Dtos;

class PaymentRequest
{
    public function __construct(
        public readonly string $bookingId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $callbackUrl = null,
        public readonly ?array $metadata = null,
    ) {}

    public static function fromBooking(\App\Models\Booking $booking): self
    {
        return new self(
            bookingId: $booking->id,
            amount: $booking->total_amount,
            currency: "ZMW",
            email: $booking->guest_email,
            phone: $booking->guest_phone,
            firstName: $booking->guest_first_name,
            lastName: $booking->guest_last_name,
            callbackUrl: config("app.url") . "/api/v1/payments/callback",
            metadata: [
                "booking_number" => $booking->booking_number,
                "accommodation_id" => $booking->accommodation_id,
                "check_in" => $booking->check_in_date->toDateString(),
                "check_out" => $booking->check_out_date->toDateString(),
            ],
        );
    }

    public function toArray(): array
    {
        return [
            "booking_id" => $this->bookingId,
            "amount" => $this->amount,
            "currency" => $this->currency,
            "email" => $this->email,
            "phone" => $this->phone,
            "first_name" => $this->firstName,
            "last_name" => $this->lastName,
            "callback_url" => $this->callbackUrl,
            "metadata" => $this->metadata,
        ];
    }
}
