<?php

namespace App\Services\Payment\Gateways;

use App\DTOs\PaymentRequest;
use App\DTOs\PaymentResponse;
use App\Models\Payment;
use App\Services\Payment\AbstractPaymentGateway;

class FlutterwaveGateway extends AbstractPaymentGateway
{
    public function __construct()
    {
        $this->baseUrl = 'https://api.flutterwave.com/v3';
        $this->secretKey = config('services.flutterwave.secret_key');
        $this->publicKey = config('services.flutterwave.public_key');
    }

    public function getName(): string
    {
        return 'flutterwave';
    }

    protected function getDefaultHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->secretKey,
            'Content-Type' => 'application/json',
        ];
    }

    public function initializePayment(PaymentRequest $request): PaymentResponse
    {
        try {
            $reference = $this->generateReference($request->bookingId);

            $response = $this->makeRequest('post', '/payments', [
                'tx_ref' => $reference,
                'amount' => $request->amount,
                'currency' => $request->currency,
                'redirect_url' => $request->callbackUrl ?? config('app.frontend_url') . '/payment/callback',
                'payment_options' => 'card,mobilemoney,ussd',
                'customer' => [
                    'email' => $request->email,
                    'phonenumber' => $request->phone,
                    'name' => $request->firstName . ' ' . $request->lastName,
                ],
                'customizations' => [
                    'title' => 'Lodge Booking Payment',
                    'description' => 'Booking #' . ($request->metadata['booking_number'] ?? ''),
                    'logo' => config('app.logo_url'),
                ],
                'meta' => $request->metadata,
            ]);

            $this->logPayment('initialized', [
                'reference' => $reference,
                'response' => $response,
            ]);

            if ($response['status'] === 'success') {
                return PaymentResponse::success(
                    transactionId: $response['data']['id'] ?? $reference,
                    reference: $reference,
                    authorizationUrl: $response['data']['link'],
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
            $response = $this->makeRequest('get', "/transactions/verify_by_reference?tx_ref={$reference}");

            if ($response['status'] !== 'success') {
                return PaymentResponse::failed($response['message'] ?? 'Verification failed');
            }

            $transaction = $response['data'];
            $status = $this->mapStatus($transaction['status']);

            $this->logPayment('verified', [
                'reference' => $reference,
                'status' => $status->value,
            ]);

            return PaymentResponse::verified(
                transactionId: $transaction['id'],
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
            $response = $this->makeRequest('post', '/transactions/' . $payment->transaction_id . '/refund', [
                'amount' => $amount,
            ]);

            if ($response['status'] === 'success') {
                $this->logPayment('refunded', [
                    'payment_id' => $payment->id,
                    'refund_id' => $response['data']['id'] ?? null,
                ]);

                return PaymentResponse::success(
                    transactionId: $response['data']['id'] ?? $payment->transaction_id,
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

        if ($event === 'charge.completed') {
            $transaction = $payload['data'];
            $status = $this->mapStatus($transaction['status']);

            return PaymentResponse::verified(
                transactionId: $transaction['id'],
                reference: $transaction['tx_ref'],
                status: $status->value,
                amount: $transaction['amount'],
                currency: $transaction['currency'],
                data: $transaction
            );
        }

        return PaymentResponse::failed('Unknown event type');
    }

    protected function validateWebhookSignature(array $payload, ?string $signature): bool
    {
        $webhookHash = config('services.flutterwave.webhook_hash');

        if (!$webhookHash || !$signature) {
            return false;
        }

        return $signature === $webhookHash;
    }
}
