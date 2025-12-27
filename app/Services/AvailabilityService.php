<?php

namespace App\Services;

use App\Models\Accommodation;
use App\Models\Booking;
use App\Models\BlockedDate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AvailabilityService
{
    /**
     * Check if accommodation is available for the given date range
     */
    public function checkAvailability(
        Accommodation $accommodation,
        Carbon $checkIn,
        Carbon $checkOut,
    ): array {
        // Check if dates are valid
        if ($checkIn->gte($checkOut)) {
            return [
                "available" => false,
                "message" => "Check-out date must be after check-in date.",
            ];
        }

        // Check minimum stay requirement
        $nights = $checkIn->diffInDays($checkOut);
        if ($nights < $accommodation->minimum_stay) {
            return [
                "available" => false,
                "message" => "Minimum stay is {$accommodation->minimum_stay} night(s).",
            ];
        }

        // Check maximum stay requirement
        if (
            $accommodation->maximum_stay &&
            $nights > $accommodation->maximum_stay
        ) {
            return [
                "available" => false,
                "message" => "Maximum stay is {$accommodation->maximum_stay} night(s).",
            ];
        }

        // Check if accommodation is available
        if ($accommodation->status !== "available") {
            return [
                "available" => false,
                "message" => "This accommodation is currently unavailable.",
            ];
        }

        // Check for existing bookings
        $hasBookings = Booking::where("accommodation_id", $accommodation->id)
            ->whereNotIn("status", ["cancelled"])
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query
                    ->whereBetween("check_in_date", [$checkIn, $checkOut])
                    ->orWhereBetween("check_out_date", [$checkIn, $checkOut])
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where("check_in_date", "<=", $checkIn)->where(
                            "check_out_date",
                            ">=",
                            $checkOut,
                        );
                    });
            })
            ->exists();

        if ($hasBookings) {
            return [
                "available" => false,
                "message" =>
                    "This accommodation is already booked for the selected dates.",
            ];
        }

        // Check for blocked dates
        $hasBlockedDates = BlockedDate::where(
            "accommodation_id",
            $accommodation->id,
        )
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query
                    ->whereBetween("start_date", [$checkIn, $checkOut])
                    ->orWhereBetween("end_date", [$checkIn, $checkOut])
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where("start_date", "<=", $checkIn)->where(
                            "end_date",
                            ">=",
                            $checkOut,
                        );
                    });
            })
            ->exists();

        if ($hasBlockedDates) {
            return [
                "available" => false,
                "message" =>
                    "This accommodation is unavailable for the selected dates.",
            ];
        }

        return [
            "available" => true,
            "message" => "This accommodation is available for booking.",
            "nights" => $nights,
        ];
    }

    /**
     * Get unavailable dates for an accommodation
     */
    public function getUnavailableDates(
        Accommodation $accommodation,
        int $months = 6,
    ): array {
        $endDate = now()->addMonths($months);
        $unavailableDates = [];

        // Get booked dates
        $bookings = Booking::where("accommodation_id", $accommodation->id)
            ->whereNotIn("status", ["cancelled"])
            ->where("check_out_date", ">=", now())
            ->where("check_in_date", "<=", $endDate)
            ->get(["check_in_date", "check_out_date"]);

        foreach ($bookings as $booking) {
            $period = Carbon::parse($booking->check_in_date)->daysUntil(
                $booking->check_out_date,
            );

            foreach ($period as $date) {
                $unavailableDates[] = $date->format("Y-m-d");
            }
        }

        // Get blocked dates
        $blockedDates = BlockedDate::where(
            "accommodation_id",
            $accommodation->id,
        )
            ->where("end_date", ">=", now())
            ->where("start_date", "<=", $endDate)
            ->get(["start_date", "end_date"]);

        foreach ($blockedDates as $blocked) {
            $period = Carbon::parse($blocked->start_date)->daysUntil(
                $blocked->end_date->addDay(),
            );

            foreach ($period as $date) {
                $unavailableDates[] = $date->format("Y-m-d");
            }
        }

        return array_unique($unavailableDates);
    }
}
