<?php

namespace App\Services;

use App\Models\Accommodation;
use App\Models\Activity;
use Carbon\Carbon;

class PricingService
{
    private const TAX_RATE = 0.18; // 18% tax

    private const SERVICE_FEE_RATE = 0.05; // 5% service fee

    /**
     * Calculate total price for a booking
     */
    public function calculateBookingPrice(
        Accommodation $accommodation,
        string|Carbon $checkIn,
        string|Carbon $checkOut,
        float $discount = 0
    ): array {
        $checkIn = $checkIn instanceof Carbon ? $checkIn : Carbon::parse($checkIn);
        $checkOut = $checkOut instanceof Carbon ? $checkOut : Carbon::parse($checkOut);

        $nights = $checkIn->diffInDays($checkOut);
        $breakdown = [];
        $subtotal = 0;

        // Calculate nightly rates
        $period = $checkIn->daysUntil($checkOut);

        foreach ($period as $date) {
            $isWeekend = $date->isWeekend();
            $rate = $isWeekend ? $accommodation->weekend_price : $accommodation->base_price;

            $breakdown[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->format('l'),
                'rate' => (float) $rate,
                'type' => $isWeekend ? 'weekend' : 'weekday',
            ];

            $subtotal += $rate;
        }

        // Calculate fees
        $cleaningFee = (float) $accommodation->cleaning_fee;
        $serviceFee = $subtotal * self::SERVICE_FEE_RATE;
        $subtotalBeforeTax = $subtotal + $cleaningFee + $serviceFee;

        // Apply discount
        $discountAmount = (float) $discount;
        $subtotalAfterDiscount = $subtotalBeforeTax - $discountAmount;

        // Calculate tax on subtotal after discount
        $taxAmount = $subtotalAfterDiscount * self::TAX_RATE;

        // Calculate total
        $total = $subtotalAfterDiscount + $taxAmount;

        return [
            'nights' => $nights,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
            'nightly_breakdown' => $breakdown,
            'subtotal' => round($subtotal, 2),
            'cleaning_fee' => round($cleaningFee, 2),
            'service_fee' => round($serviceFee, 2),
            'discount' => round($discountAmount, 2),
            'subtotal_before_tax' => round($subtotalAfterDiscount, 2),
            'tax_amount' => round($taxAmount, 2),
            'tax_rate' => self::TAX_RATE * 100 .'%',
            'total_amount' => round($total, 2),
            'currency' => 'UGX',
        ];
    }

    /**
     * Calculate price for activity
     */
    public function calculateActivityPrice(
        Activity $activity,
        int $adults = 1,
        int $children = 0,
        bool $isGroup = false
    ): array {
        if ($isGroup && $activity->group_price) {
            $total = (float) $activity->group_price;

            return [
                'pricing_type' => 'group',
                'participants' => [
                    'adults' => $adults,
                    'children' => $children,
                    'total' => $adults + $children,
                ],
                'rates' => [
                    'group_rate' => (float) $activity->group_price,
                ],
                'total' => round($total, 2),
                'currency' => 'UGX',
            ];
        }

        // Individual pricing
        $adultPrice = $activity->adult_price ?? $activity->price;
        $childPrice = $activity->child_price ?? ($activity->price * 0.7);

        $adultTotal = $adults * $adultPrice;
        $childTotal = $children * $childPrice;
        $total = $adultTotal + $childTotal;

        return [
            'pricing_type' => 'individual',
            'participants' => [
                'adults' => $adults,
                'children' => $children,
                'total' => $adults + $children,
            ],
            'rates' => [
                'adult_price' => (float) $adultPrice,
                'child_price' => (float) $childPrice,
            ],
            'breakdown' => [
                'adults_subtotal' => round($adultTotal, 2),
                'children_subtotal' => round($childTotal, 2),
            ],
            'total' => round($total, 2),
            'currency' => 'UGX',
        ];
    }

    /**
     * Validate payment amount matches booking total
     */
    public function validatePaymentAmount(float $paymentAmount, float $bookingTotal): bool
    {
        // Allow for small rounding differences (1 currency unit)
        return abs($paymentAmount - $bookingTotal) < 1;
    }

    /**
     * Calculate refund amount based on cancellation policy
     */
    public function calculateRefundAmount(
        float $totalAmount,
        Carbon $checkInDate,
        ?Carbon $cancellationDate = null
    ): array {
        $cancellationDate = $cancellationDate ?? now();
        $daysUntilCheckIn = $cancellationDate->diffInDays($checkInDate, false);

        // Refund policy:
        // - More than 7 days: 100% refund
        // - 3-7 days: 50% refund
        // - Less than 3 days: No refund

        if ($daysUntilCheckIn > 7) {
            $refundPercentage = 100;
        } elseif ($daysUntilCheckIn >= 3) {
            $refundPercentage = 50;
        } else {
            $refundPercentage = 0;
        }

        $refundAmount = ($totalAmount * $refundPercentage) / 100;

        return [
            'total_paid' => round($totalAmount, 2),
            'refund_percentage' => $refundPercentage,
            'refund_amount' => round($refundAmount, 2),
            'days_until_checkin' => max(0, $daysUntilCheckIn),
            'cancellation_date' => $cancellationDate->toDateString(),
            'check_in_date' => $checkInDate->toDateString(),
            'policy' => $this->getCancellationPolicyText($daysUntilCheckIn),
        ];
    }

    /**
     * Get cancellation policy text
     */
    private function getCancellationPolicyText(int $daysUntilCheckIn): string
    {
        if ($daysUntilCheckIn > 7) {
            return 'Free cancellation - Full refund';
        } elseif ($daysUntilCheckIn >= 3) {
            return 'Partial refund - 50% of booking amount';
        } else {
            return 'No refund - Within 3 days of check-in';
        }
    }
}
