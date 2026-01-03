<?php

namespace App\Services\Payment\Gateways;

use App\DTOs\PaymentRequest;
use App\DTOs\PaymentResponse;
use App\Models\Payment;
use App\Services\Payment\AbstractPaymentGateway;

class IotecGateway extends AbstractPaymentGateway
{
    public function __construct()
    {
        $this->baseUrl = config('services.iotec.base_url', 'https://api.iotec.io');
        $this->secretKey = config('services.iotec.secret_key');
        $this->publicKey = config('services.iotec.public_key');
    }

    public function getName(): string
    {
        return 'iotec';
    }

    protected function getDefaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    public function initializePayment(PaymentRequest $request): PaymentResponse
    {
        try {
            $reference = $this->generateReference($request->bookingId);

            $response = $this->makeRequest('post', '/v1/payments/initialize', [
                'reference' => $reference,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'email' => $request->email,
                'phone' => $request->phone,
                'customer_name' => $request->firstName . ' ' . $request->lastName,
                'callback_url' => $request->callbackUrl ?? config('app.frontend_url') . '/payment/callback',
                'return_url' => config('app.frontend_url') . '/payment/success',
                'description' => 'Booking #' . ($request->metadata['booking_number'] ?? ''),
                'metadata' => $request->metadata,
            ]);

            $this->logPayment('initialized', [
                'reference' => $reference,
                'transaction_id' => $response['data']['transaction_id'] ?? null,
            ]);

            if ($response['status'] === 'success' || $response['success'] === true) {
                return PaymentResponse::success(
                    transactionId: $response['data']['transaction_id'],
                    reference: $reference,
                    authorizationUrl: $response['data']['authorization_url'],
                    data: $response['data']
                );
            }

            return PaymentResponse::failed($response['message'] ?? 'Payment initialization failed');

        } catch (\Exception $e) {
            $this->logPayment('initialization_failed', ['error' => $e->getMessage()]);
            return PaymentResponse::failed($e->getMessage());
        }
    }

    public function verifyPayment(string $reference): PaymentResponse
    {
        try {
            $response = $this->makeRequest('get', "/v1/payments/verify/{$reference}");

            if (!($response['status'] === 'success' || $response['success'] === true)) {
                return PaymentResponse::failed($response['message'] ?? 'Verification failed');
            }

            $transaction = $response['data'];
            $status = $this->mapStatus($transaction['status']);

            $this->logPayment('verified', [
                'reference' => $reference,
                'status' => $status->value,
            ]);

            return PaymentResponse::verified(
                transactionId: $transaction['transaction_id'],
                reference: $reference,
                status: $status->value,
                amount: $transaction['amount'],
                currency: $transaction['currency'],
                data: $transaction
            );

        } catch (\Exception $e) {
            $this->logPayment('verification_failed', ['error' => $e->getMessage()]);
            return PaymentResponse::failed($e->getMessage());
        }
    }

    public function refund(Payment $payment, float $amount, ?string $reason = null): PaymentResponse
    {
        try {
            $response = $this->makeRequest('post', '/v1/payments/refund', [
                'transaction_id' => $payment->transaction_id,
                'amount' => $amount,
                'reason' => $reason ?? 'Customer request',
            ]);

            if ($response['status'] === 'success' || $response['success'] === true) {
                $this->logPayment('refunded', [
                    'payment_id' => $payment->id,
                    'refund_id' => $response['data']['refund_id'] ?? null,
                ]);

                return PaymentResponse::success(
                    transactionId: $response['data']['refund_id'] ?? $payment->transaction_id,
                    reference: $payment->transaction_id,
                    data: $response['data']
                );
            }

            return PaymentResponse::failed($response['message'] ?? 'Refund failed');

        } catch (\Exception $e) {
            $this->logPayment('refund_failed', ['error' => $e->getMessage()]);
            return PaymentResponse::failed($e->getMessage());
        }
    }

    public function handleWebhook(array $payload): PaymentResponse
    {
        $event = $payload['event'] ?? null;
        $data = $payload['data'] ?? [];

        if ($event === 'payment.success' || $event === 'payment.completed') {
            $status = $this->mapStatus($data['status'] ?? 'completed');

            return PaymentResponse::verified(
                transactionId: $data['transaction_id'],
                reference: $data['reference'],
                status: $status->value,
                amount: $data['amount'],
                currency: $data['currency'],
                data: $data
            );
        }

        if ($event === 'payment.failed') {
            return PaymentResponse::failed('Payment failed', $data);
        }

        return PaymentResponse::failed('Unknown event type');
    }

    protected function validateWebhookSignature(array $payload, ?string $signature): bool
    {
        if (!$signature) {
            return false;
        }

        $webhookSecret = config('services.iotec.webhook_secret');
        if (!$webhookSecret) {
            return false;
        }

        $computedSignature = hash_hmac('sha256', json_encode($payload), $webhookSecret);

        return hash_equals($computedSignature, $signature);
    }
}
