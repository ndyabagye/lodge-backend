<?php

namespace App\Services\Payment\Gateways;

use App\DTOs\PaymentRequest;
use App\DTOs\PaymentResponse;
use App\Models\Payment;
use App\Services\Payment\AbstractPaymentGateway;

class PesapalGateway extends AbstractPaymentGateway
{
    private ?string $token = null;

    public function __construct()
    {
        $this->baseUrl = config('services.pesapal.sandbox')
            ? 'https://cybqa.pesapal.com/pesapalv3'
            : 'https://pay.pesapal.com/v3';

        $this->secretKey = config('services.pesapal.consumer_secret');
        $this->publicKey = config('services.pesapal.consumer_key');
    }

    public function getName(): string
    {
        return 'pesapal';
    }

    protected function getDefaultHeaders(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ($this->token) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }

        return $headers;
    }

    /**
     * Authenticate and get token
     */
    private function authenticate(): void
    {
        if ($this->token) {
            return;
        }

        $response = $this->makeRequest('post', '/api/Auth/RequestToken', [
            'consumer_key' => $this->publicKey,
            'consumer_secret' => $this->secretKey,
        ]);

        $this->token = $response['token'];
    }

    public function initializePayment(PaymentRequest $request): PaymentResponse
    {
        try {
            $this->authenticate();
            $reference = $this->generateReference($request->bookingId);

            $response = $this->makeRequest('post', '/api/Transactions/SubmitOrderRequest', [
                'id' => $reference,
                'currency' => $request->currency,
                'amount' => $request->amount,
                'description' => 'Booking #' . ($request->metadata['booking_number'] ?? ''),
                'callback_url' => $request->callbackUrl ?? config('app.url') . '/api/v1/payments/pesapal/callback',
                'notification_id' => config('services.pesapal.ipn_id'),
                'billing_address' => [
                    'email_address' => $request->email,
                    'phone_number' => $request->phone,
                    'first_name' => $request->firstName,
                    'last_name' => $request->lastName,
                ],
            ]);

            $this->logPayment('initialized', [
                'reference' => $reference,
                'order_tracking_id' => $response['order_tracking_id'] ?? null,
            ]);

            return PaymentResponse::success(
                transactionId: $response['order_tracking_id'],
                reference: $reference,
                authorizationUrl: $response['redirect_url'],
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
            $this->authenticate();

            $response = $this->makeRequest('get', "/api/Transactions/GetTransactionStatus?orderTrackingId={$reference}");

            $status = $this->mapStatus($response['payment_status_description'] ?? 'pending');

            $this->logPayment('verified', [
                'reference' => $reference,
                'status' => $status->value,
            ]);

            return PaymentResponse::verified(
                transactionId: $response['order_tracking_id'],
                reference: $reference,
                status: $status->value,
                amount: $response['amount'],
                currency: $response['currency'],
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
            $this->authenticate();

            $response = $this->makeRequest('post', '/api/Transactions/RefundRequest', [
                'confirmation_code' => $payment->transaction_id,
                'amount' => $amount,
                'username' => config('services.pesapal.username'),
                'remarks' => $reason ?? 'Refund requested',
            ]);

            $this->logPayment('refunded', [
                'payment_id' => $payment->id,
                'response' => $response,
            ]);

            return PaymentResponse::success(
                transactionId: $response['refund_id'] ?? $payment->transaction_id,
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
        $orderTrackingId = $payload['OrderTrackingId'] ?? null;

        if (!$orderTrackingId) {
            return PaymentResponse::failed('Missing order tracking ID');
        }

        return $this->verifyPayment($orderTrackingId);
    }

    protected function validateWebhookSignature(array $payload, ?string $signature): bool
    {
        // Pesapal uses IPN (Instant Payment Notification) validation
        // The actual validation happens during verifyPayment
        return true;
    }
}
