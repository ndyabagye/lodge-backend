<?php

namespace App\Dtos;

class PaymentResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $transactionId = null,
        public readonly ?string $reference = null,
        public readonly ?string $authorizationUrl = null,
        public readonly ?string $status = null,
        public readonly ?float $amount = null,
        public readonly ?string $currency = null,
        public readonly ?string $message = null,
        public readonly ?array $data = null
    ) {}

    public static function success(
        string $transactionId,
        string $reference,
        ?string $authorizationUrl = null,
        ?array $data = null
    ): self {
        return new self(
            success: true,
            transactionId: $transactionId,
            reference: $reference,
            authorizationUrl: $authorizationUrl,
            status: 'pending',
            data: $data
        );
    }

    public static function failed(string $message, ?array $data = null): self
    {
        return new self(
            success: false,
            message: $message,
            data: $data
        );
    }

    public static function verified(
        string $transactionId,
        string $reference,
        string $status,
        float $amount,
        string $currency,
        ?array $data = null
    ): self {
        return new self(
            success: true,
            transactionId: $transactionId,
            reference: $reference,
            status: $status,
            amount: $amount,
            currency: $currency,
            data: $data
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'success' => $this->success,
            'transaction_id' => $this->transactionId,
            'reference' => $this->reference,
            'authorization_url' => $this->authorizationUrl,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'message' => $this->message,
            'data' => $this->data,
        ], fn($value) => $value !== null);
    }
}
