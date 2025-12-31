<?php

namespace App\Contracts;

namespace App\Contracts;

use App\DTOs\PaymentRequest;
use App\DTOs\PaymentResponse;
use App\Models\Booking;
use App\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Initialize a payment
     */
    public function initializePayment(PaymentRequest $request): PaymentResponse;

    /**
     * Verify a payment
     */
    public function verifyPayment(string $reference): PaymentResponse;

    /**
     * Process refund
     */
    public function refund(Payment $payment, float $amount, ?string $reason = null): PaymentResponse;

    /**
     * Handle webhook from payment gateway
     */
    public function handleWebhook(array $payload): PaymentResponse;

    /**
     * Get payment gateway name
     */
    public function getName(): string;

    /**
     * Check if gateway is available
     */
    public function isAvailable(): bool;
}
