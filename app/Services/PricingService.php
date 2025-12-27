<?php

namespace App\Services;

use App\Models\Accommodation;
use Carbon\Carbon;

class PricingService
{
    private const TAX_RATE = 0.18; // 18% tax

    /**
     * Calculate total price for a booking
     */
    public function calculatePrice(
        Accommodation $accommodation,
        Carbon $checkIn,
        Carbon $checkOut,
        array $options = [],
    ): array {
        $nights = $checkIn->diffInDays($checkOut);

        // Calculate base price
        $subtotal = $this->calculateSubtotal(
            $accommodation,
            $checkIn,
            $checkOut,
            $nights,
        );

        // Get fees
        $cleaningFee = $accommodation->cleaning_fee;
        $serviceFee = $options["service_fee"] ?? 0;
        $discount = $options["discount"] ?? 0;

        // Calculate tax (on subtotal + cleaning fee)
        $taxableAmount = $subtotal + $cleaningFee;
        $taxAmount = round($taxableAmount * self::TAX_RATE, 2);

        // Calculate total
        $total =
            $subtotal + $cleaningFee + $serviceFee + $taxAmount - $discount;

        return [
            "nights" => $nights,
            "subtotal" => round($subtotal, 2),
            "cleaning_fee" => round($cleaningFee, 2),
            "service_fee" => round($serviceFee, 2),
            "tax_amount" => $taxAmount,
            "discount" => round($discount, 2),
            "total_amount" => round($total, 2),
            "breakdown" => $this->getPriceBreakdown(
                $accommodation,
                $checkIn,
                $checkOut,
            ),
        ];
    }

    /**
     * Calculate subtotal based on night prices
     */
    private function calculateSubtotal(
        Accommodation $accommodation,
        Carbon $checkIn,
        Carbon $checkOut,
        int $nights,
    ): float {
        $subtotal = 0;
        $current = $checkIn->copy();

        for ($i = 0; $i < $nights; $i++) {
            $price = $this->getPriceForDate($accommodation, $current);
            $subtotal += $price;
            $current->addDay();
        }

        return $subtotal;
    }

    /**
     * Get price for a specific date
     */
    private function getPriceForDate(
        Accommodation $accommodation,
        Carbon $date,
    ): float {
        // Check if it's a weekend (Friday or Saturday)
        if (in_array($date->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY])) {
            return $accommodation->weekend_price;
        }

        return $accommodation->base_price;
    }

    /**
     * Get detailed price breakdown by date
     */
    private function getPriceBreakdown(
        Accommodation $accommodation,
        Carbon $checkIn,
        Carbon $checkOut,
    ): array {
        $breakdown = [];
        $current = $checkIn->copy();
        $nights = $checkIn->diffInDays($checkOut);

        for ($i = 0; $i < $nights; $i++) {
            $price = $this->getPriceForDate($accommodation, $current);
            $breakdown[] = [
                "date" => $current->format("Y-m-d"),
                "day" => $current->format("l"),
                "price" => $price,
                "is_weekend" => in_array($current->dayOfWeek, [
                    Carbon::FRIDAY,
                    Carbon::SATURDAY,
                ]),
            ];
            $current->addDay();
        }

        return $breakdown;
    }

    /**
     * Apply discount code (placeholder for future implementation)
     */
    public function applyDiscount(float $subtotal, string $discountCode): float
    {
        // TODO: Implement discount code logic
        return 0;
    }
}
