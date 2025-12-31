<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentRequest;
use App\DTOs\PaymentResponse;
use App\Enums\PaymentStatus as PaymentStatusEnum;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractPaymentGateway implements PaymentGatewayInterface
{
    protected string $baseUrl;
    protected string $secretKey;
    protected string $publicKey;

    /**
     * Make HTTP request to gateway
     */
    protected function makeRequest(
        string $method,
        string $endpoint,
        array $data = [],
        array $headers = []
    ): array {
        try {
            $url = $this->baseUrl . $endpoint;

            $response = Http::withHeaders(array_merge(
                $this->getDefaultHeaders(),
                $headers
            ))->$method($url, $data);

            if (!$response->successful()) {
                Log::error("Payment gateway request failed", [
                    'gateway' => $this->getName(),
                    'url' => $url,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new \Exception($response->body());
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error("Payment gateway exception", [
                'gateway' => $this->getName(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get default headers for requests
     */
    abstract protected function getDefaultHeaders(): array;

    /**
     * Generate unique reference
     */
    protected function generateReference(string $bookingId): string
    {
        return strtoupper($this->getName()) . '-' . $bookingId . '-' . time();
    }

    /**
     * Map gateway status to internal status
     */
    protected function mapStatus(string $gatewayStatus): PaymentStatusEnum
    {
        return match (strtolower($gatewayStatus)) {
            'success', 'successful', 'completed', 'approved' => PaymentStatusEnum::COMPLETED,
            'failed', 'declined', 'cancelled' => PaymentStatusEnum::FAILED,
            'pending', 'processing' => PaymentStatusEnum::PENDING,
            'refunded' => PaymentStatusEnum::REFUNDED,
            default => PaymentStatusEnum::PENDING,
        };
    }

    /**
     * Log payment activity
     */
    protected function logPayment(string $action, array $data): void
    {
        Log::info("Payment {$action}", [
            'gateway' => $this->getName(),
            'data' => $data,
        ]);
    }

    /**
     * Validate webhook signature
     */
    abstract protected function validateWebhookSignature(array $payload, ?string $signature): bool;

    /**
     * Check if gateway is available
     */
    public function isAvailable(): bool
    {
        return !empty($this->secretKey) && !empty($this->publicKey);
    }
}
