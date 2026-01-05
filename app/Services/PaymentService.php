<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\DTOs\PaymentRequest;
use App\Enums\BookingStatus;
use App\Enums\PaymentGateway as PaymentGatewayEnum;
use App\Enums\PaymentStatus as PaymentStatusEnum;
use App\Exceptions\PaymentException;
use App\Models\Booking;
use App\Models\Payment;
use App\Notifications\PaymentReceived;
use App\Services\Payment\Gateways\FlutterwaveGateway;
use App\Services\Payment\Gateways\IotecGateway;
use App\Services\Payment\Gateways\PesapalGateway;
use App\Services\Payment\Gateways\StripeGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PaymentService
{
    private array $gateways = [];

    public function __construct()
    {
        // Register available gateways
        $this->registerGateway("stripe", new StripeGateway());
        $this->registerGateway("flutterwave", new FlutterwaveGateway());
        $this->registerGateway("pesapal", new PesapalGateway());
        $this->registerGateway("iotec", new IotecGateway());
    }

    /**
     * Register a payment gateway
     */
    private function registerGateway(
        string $name,
        PaymentGatewayInterface $gateway,
    ): void {
        if ($gateway->isAvailable()) {
            $this->gateways[$name] = $gateway;
        }
    }

    /**
     * Get a payment gateway instance
     */
    public function getGateway(string $name): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$name])) {
            throw PaymentException::gatewayError(
                "Gateway '{$name}' is not available or configured",
            );
        }

        return $this->gateways[$name];
    }

    /**
     * Get all available gateways
     */
    public function getAvailableGateways(): array
    {
        return array_keys($this->gateways);
    }

    /**
     * Initialize payment for a booking
     */
    public function initializePayment(
        Booking $booking,
        string $gatewayName,
    ): array {
        return DB::transaction(function () use ($booking, $gatewayName) {
            // Check if booking is already paid
            if ($booking->payment_status === PaymentStatusEnum::PAID) {
                throw PaymentException::alreadyPaid();
            }

            // Get gateway
            $gateway = $this->getGateway($gatewayName);

            // Create payment request
            $paymentRequest = PaymentRequest::fromBooking($booking);

            // Initialize payment with gateway
            $response = $gateway->initializePayment($paymentRequest);

            if (!$response->success) {
                throw PaymentException::failed(
                    $response->message ?? "Payment initialization failed",
                );
            }

            // Create payment record
            $payment = Payment::create([
                "booking_id" => $booking->id,
                "transaction_id" => $response->transactionId,
                "amount" => $booking->total_amount,
                "currency" => "ZMW",
                "payment_method" => $booking->payment_method,
                "payment_gateway" => $gatewayName,
                "status" => PaymentStatusEnum::PENDING,
                "metadata" => [
                    "reference" => $response->reference,
                    "gateway_response" => $response->data,
                ],
            ]);

            Log::info("Payment initialized", [
                "booking_id" => $booking->id,
                "payment_id" => $payment->id,
                "gateway" => $gatewayName,
            ]);

            return [
                "payment" => $payment,
                "authorization_url" => $response->authorizationUrl,
                "reference" => $response->reference,
            ];
        });
    }

    /**
     * Verify payment
     */
    public function verifyPayment(
        string $reference,
        string $gatewayName,
    ): Payment {
        return DB::transaction(function () use ($reference, $gatewayName) {
            $gateway = $this->getGateway($gatewayName);

            // Verify with gateway
            $response = $gateway->verifyPayment($reference);

            if (!$response->success) {
                throw PaymentException::failed(
                    $response->message ?? "Payment verification failed",
                );
            }

            // Find payment by reference
            $payment = Payment::whereJsonContains(
                "metadata->reference",
                $reference,
            )->firstOrFail();

            // Map gateway status to internal status
            $status = match (strtolower($response->status)) {
                "completed",
                "success",
                "successful"
                    => PaymentStatusEnum::COMPLETED,
                "failed", "declined" => PaymentStatusEnum::FAILED,
                default => PaymentStatusEnum::PENDING,
            };

            // Update payment
            $payment->update([
                "status" => $status,
                "metadata" => array_merge($payment->metadata ?? [], [
                    "verification_response" => $response->data,
                    "verified_at" => now()->toISOString(),
                ]),
            ]);

            // Update booking if payment completed
            if ($status === PaymentStatusEnum::COMPLETED) {
                $this->handleSuccessfulPayment($payment);
            } elseif ($status === PaymentStatusEnum::FAILED) {
                $payment->booking->update([
                    "payment_status" => PaymentStatusEnum::FAILED,
                ]);
            }

            Log::info("Payment verified", [
                "payment_id" => $payment->id,
                "status" => $status->value,
            ]);

            return $payment->refresh("booking");
        });
    }

    /**
     * Handle successful payment
     */
    private function handleSuccessfulPayment(Payment $payment): void
    {
        $booking = $payment->booking;

        // Update booking payment status
        $booking->update([
            "payment_status" => PaymentStatusEnum::PAID,
            "status" => BookingStatus::CONFIRMED,
        ]);

        // Send payment confirmation email
        Notification::route("mail", $booking->guest_email)->notify(
            new PaymentReceived($payment),
        );

        Log::info("Payment completed", [
            "booking_id" => $booking->id,
            "payment_id" => $payment->id,
            "amount" => $payment->amount,
        ]);
    }

    /**
     * Process refund
     */
    public function processRefund(
        Payment $payment,
        float $amount,
        ?string $reason = null,
    ): Payment {
        return DB::transaction(function () use ($payment, $amount, $reason) {
            // Validate refund
            if (!$payment->isCompleted()) {
                throw PaymentException::failed(
                    "Cannot refund a payment that is not completed",
                );
            }

            if ($amount > $payment->amount) {
                throw PaymentException::invalidAmount(
                    "Refund amount cannot exceed payment amount",
                );
            }

            // Get gateway
            $gateway = $this->getGateway($payment->payment_gateway);

            // Process refund
            $response = $gateway->refund($payment, $amount, $reason);

            if (!$response->success) {
                throw PaymentException::failed(
                    $response->message ?? "Refund failed",
                );
            }

            // Update payment
            $payment->update([
                "status" => PaymentStatusEnum::REFUNDED,
                "metadata" => array_merge($payment->metadata ?? [], [
                    "refund_response" => $response->data,
                    "refund_amount" => $amount,
                    "refund_reason" => $reason,
                    "refunded_at" => now()->toISOString(),
                ]),
            ]);

            // Update booking
            $payment->booking->update([
                "payment_status" => PaymentStatusEnum::REFUNDED,
            ]);

            Log::info("Refund processed", [
                "payment_id" => $payment->id,
                "amount" => $amount,
                "reason" => $reason,
            ]);

            return $payment->refresh("booking");
        });
    }

    /**
     * Handle webhook from payment gateway
     */
    public function handleWebhook(
        string $gatewayName,
        array $payload,
        ?string $signature = null,
    ): ?Payment {
        try {
            $gateway = $this->getGateway($gatewayName);

            // Validate webhook signature
            if (!$gateway->validateWebhookSignature($payload, $signature)) {
                Log::warning("Invalid webhook signature", [
                    "gateway" => $gatewayName,
                ]);
                throw PaymentException::gatewayError(
                    "Invalid webhook signature",
                );
            }

            // Handle webhook
            $response = $gateway->handleWebhook($payload);

            if (!$response->success) {
                Log::error("Webhook handling failed", [
                    "gateway" => $gatewayName,
                    "message" => $response->message,
                ]);

                return null;
            }

            // Find and update payment
            if ($response->reference) {
                $payment = Payment::whereJsonContains(
                    "metadata->reference",
                    $response->reference,
                )->first();

                if ($payment) {
                    return $this->verifyPayment(
                        $response->reference,
                        $gatewayName,
                    );
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Webhook error", [
                "gateway" => $gatewayName,
                "error" => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStats(
        \Carbon\Carbon $from,
        \Carbon\Carbon $to,
    ): array {
        $payments = Payment::whereBetween("created_at", [$from, $to])->get();

        $completedPayments = $payments->where(
            "status",
            PaymentStatusEnum::COMPLETED,
        );
        $failedPayments = $payments->where("status", PaymentStatusEnum::FAILED);
        $refundedPayments = $payments->where(
            "status",
            PaymentStatusEnum::REFUNDED,
        );

        return [
            "period" => [
                "from" => $from->toDateString(),
                "to" => $to->toDateString(),
            ],
            "total_payments" => $payments->count(),
            "completed_payments" => $completedPayments->count(),
            "failed_payments" => $failedPayments->count(),
            "refunded_payments" => $refundedPayments->count(),
            "total_amount" => round($completedPayments->sum("amount"), 2),
            "refunded_amount" => round($refundedPayments->sum("amount"), 2),
            "success_rate" =>
                $payments->count() > 0
                    ? round(
                        ($completedPayments->count() / $payments->count()) *
                            100,
                        2,
                    )
                    : 0,
            "by_gateway" => $this->getPaymentsByGateway($payments),
        ];
    }

    /**
     * Get payments grouped by gateway
     */
    private function getPaymentsByGateway($payments): array
    {
        return $payments
            ->groupBy("payment_gateway")
            ->map(function ($gatewayPayments, $gateway) {
                $completed = $gatewayPayments->where(
                    "status",
                    PaymentStatusEnum::COMPLETED,
                );

                return [
                    "gateway" => $gateway,
                    "total" => $gatewayPayments->count(),
                    "completed" => $completed->count(),
                    "amount" => round($completed->sum("amount"), 2),
                ];
            })
            ->values()
            ->toArray();
    }
}
