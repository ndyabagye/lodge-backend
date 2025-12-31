<?php

namespace App\Services\Payment\Gateways;

use App\DTOs\PaymentRequest;
use App\DTOs\PaymentResponse;
use App\Models\Payment;
use App\Services\Payment\AbstractPaymentGateway;

class StripeGateway extends AbstractPaymentGateway
{
    public function __construct()
    {
        $this->baseUrl = 'https://api.stripe.com/v1';
        $this->secretKey = config('services.stripe.secret');
        $this->publicKey = config('services.stripe.key');
    }

    public function getName(): string
    {
        return 'stripe';
    }

    protected function getDefaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];
    }

    public function initializePayment(PaymentRequest $request): PaymentResponse
    {
        try {
            $reference = $this->generateReference($request->bookingId);

            // Create Stripe Checkout Session
            $response = $this->makeRequest('post', '/checkout/sessions', [
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => strtolower($request->currency),
                            'product_data' => [
                                'name' => 'Booking Payment',
                                'description' => 'Booking #' . ($request->metadata['booking_number'] ?? ''),
                            ],
                            'unit_amount' => (int)($request->amount * 100), // Convert to cents
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'payment',
                'success_url' => config('app.frontend_url') . '/bookings/payment/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => config('app.frontend_url') . '/bookings/payment/cancel',
                'client_reference_id' => $reference,
                'customer_email' => $request->email,
                'metadata' => array_merge($request->metadata ?? [], [
                    'booking_id' => $request->bookingId,
                ]),
            ]);

            $this->logPayment('initialized', [
                'reference' => $reference,
                'session_id' => $response['id'],
            ]);

            return PaymentResponse::success(
                transactionId: $response['id'],
                reference: $reference,
                authorizationUrl: $response['url'],
                data: $response
            );

        } catch (\Exception $e) {
            $this->logPayment('initialization_failed', ['error' => $e->getMessage()]);
            return PaymentResponse::failed($e->getMessage());
        }
    }

    public function verifyPayment(string $reference): PaymentResponse
    {
        try {
            // Retrieve checkout session
            $response = $this->makeRequest('get', "/checkout/sessions/{$reference}");

            $status = $this->mapStatus($response['payment_status'] ?? 'pending');

            $this->logPayment('verified', [
                'reference' => $reference,
                'status' => $status->value,
            ]);

            return PaymentResponse::verified(
                transactionId: $response['id'],
                reference: $reference,
                status: $status->value,
                amount: $response['amount_total'] / 100,
                currency: strtoupper($response['currency']),
                data: $response
            );

        } catch (\Exception $e) {
            $this->logPayment('verification_failed', ['error' => $e->getMessage()]);
            return PaymentResponse::failed($e->getMessage());
        }
    }

    public function refund(Payment $payment, float $amount, ?string $reason = null): PaymentResponse
    {
        try {
            $response = $this->makeRequest('post', '/refunds', [
                'payment_intent' => $payment->transaction_id,
                'amount' => (int)($amount * 100),
                'reason' => $reason ?? 'requested_by_customer',
            ]);

            $this->logPayment('refunded', [
                'payment_id' => $payment->id,
                'refund_id' => $response['id'],
            ]);

            return PaymentResponse::success(
                transactionId: $response['id'],
                reference: $payment->transaction_id,
                data: $response
            );

        } catch (\Exception $e) {
            $this->logPayment('refund_failed', ['error' => $e->getMessage()]);
            return PaymentResponse::failed($e->getMessage());
        }
    }

    public function handleWebhook(array $payload): PaymentResponse
    {
        $event = $payload['type'] ?? null;

        return match($event) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($payload['data']['object']),
            'payment_intent.succeeded' => $this->handlePaymentSucceeded($payload['data']['object']),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($payload['data']['object']),
            default => PaymentResponse::failed('Unknown event type'),
        };
    }

    protected function handleCheckoutCompleted(array $session): PaymentResponse
    {
        return PaymentResponse::verified(
            transactionId: $session['id'],
            reference: $session['client_reference_id'],
            status: 'completed',
            amount: $session['amount_total'] / 100,
            currency: strtoupper($session['currency']),
            data: $session
        );
    }

    protected function handlePaymentSucceeded(array $intent): PaymentResponse
    {
        return PaymentResponse::verified(
            transactionId: $intent['id'],
            reference: $intent['id'],
            status: 'completed',
            amount: $intent['amount'] / 100,
            currency: strtoupper($intent['currency']),
            data: $intent
        );
    }

    protected function handlePaymentFailed(array $intent): PaymentResponse
    {
        return PaymentResponse::failed('Payment failed', $intent);
    }

    protected function validateWebhookSignature(array $payload, ?string $signature): bool
    {
        if (!$signature) {
            return false;
        }

        $webhookSecret = config('services.stripe.webhook_secret');
        if (!$webhookSecret) {
            return false;
        }

        try {
            \Stripe\Webhook::constructEvent(
                json_encode($payload),
                $signature,
                $webhookSecret
            );
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
