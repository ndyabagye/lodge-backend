<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\PaymentException;
use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * Get available payment gateways
     */
    public function gateways(): JsonResponse
    {
        $gateways = $this->paymentService->getAvailableGateways();

        $gatewaysData = collect($gateways)->map(function ($gateway) {
            $enum = \App\Enums\PaymentGateway::from($gateway);
            return [
                'name' => $gateway,
                'label' => $enum->label(),
                'supports_card' => $enum->supportsMethod(\App\Enums\PaymentMethod::CARD),
                'supports_mobile_money' => $enum->supportsMethod(\App\Enums\PaymentMethod::MOBILE_MONEY),
            ];
        });

        return $this->successResponse($gatewaysData->toArray(), 200);
    }

    /**
     * Initialize payment for a booking
     */
    public function initialize(Request $request, Booking $booking): JsonResponse
    {
        $request->validate([
            'gateway' => 'required|string|in:stripe,flutterwave,pesapal,iotec',
        ]);

        // Authorization check
        if ($booking->user_id !== $request->user()?->id && !$request->user()?->isStaff()) {
            return $this->forbiddenResponse('You do not have permission to make payment for this booking');
        }

        try {
            $result = $this->paymentService->initializePayment(
                $booking,
                $request->gateway
            );

            return $this->successResponse([
                'payment' => new PaymentResource($result['payment']),
                'authorization_url' => $result['authorization_url'],
                'reference' => $result['reference'],
            ], 'Payment initialized successfully');

        } catch (PaymentException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->errorResponse('Payment initialization failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Verify payment
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'reference' => 'required|string',
            'gateway' => 'required|string|in:stripe,flutterwave,pesapal,iotec',
        ]);

        try {
            $payment = $this->paymentService->verifyPayment(
                $request->reference,
                $request->gateway
            );

            return $this->successResponse(
                new PaymentResource($payment->load('booking')),
                'Payment verified successfully'
            );

        } catch (PaymentException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->errorResponse('Payment verification failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Request refund
     */
    public function refund(Request $request, Payment $payment): JsonResponse
    {
        $request->validate([
            'amount' => 'nullable|numeric|min:0',
            'reason' => 'nullable|string|max:500',
        ]);

        // Only admin can process refunds
        if (!$request->user()->isStaff()) {
            return $this->forbiddenResponse('Only staff can process refunds');
        }

        try {
            $amount = $request->filled('amount') ? $request->amount : $payment->amount;

            $refundedPayment = $this->paymentService->processRefund(
                $payment,
                $amount,
                $request->reason
            );

            return $this->successResponse(
                new PaymentResource($refundedPayment->load('booking')),
                'Refund processed successfully'
            );

        } catch (PaymentException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->errorResponse('Refund failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Webhook handler for Stripe
     */
    public function stripeWebhook(Request $request): JsonResponse
    {
        try {
            $signature = $request->header('Stripe-Signature');

            $this->paymentService->handleWebhook(
                'stripe',
                $request->all(),
                $signature
            );

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Webhook handler for Flutterwave
     */
    public function flutterwaveWebhook(Request $request): JsonResponse
    {
        try {
            $signature = $request->header('verif-hash');

            $this->paymentService->handleWebhook(
                'flutterwave',
                $request->all(),
                $signature
            );

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Webhook handler for Pesapal
     */
    public function pesapalWebhook(Request $request): JsonResponse
    {
        try {
            $this->paymentService->handleWebhook(
                'pesapal',
                $request->all()
            );

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Webhook handler for IoTec
     */
    public function iotecWebhook(Request $request): JsonResponse
    {
        try {
            $signature = $request->header('X-IoTec-Signature');

            $this->paymentService->handleWebhook(
                'iotec',
                $request->all(),
                $signature
            );

            return response()->json(['status' => 'success']);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Get payment statistics (Admin only)
     */
    public function statistics(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);

        $from = \Carbon\Carbon::parse($request->from);
        $to = \Carbon\Carbon::parse($request->to);

        $stats = $this->paymentService->getPaymentStats($from, $to);

        return $this->successResponse($stats, 200);
    }

    /**
     * Download payment receipt
     */
    public function downloadReceipt(Request $request, \App\Models\Payment $payment)
{
    $booking = $payment->booking;

    // Authorization check
    if ($booking->user_id !== $request->user()->id && !$request->user()->isStaff()) {
        return $this->forbiddenResponse('You do not have access to this receipt');
    }

    $invoiceService = app(\App\Services\InvoiceService::class);

    return $invoiceService->generateReceipt($payment);
}
}
