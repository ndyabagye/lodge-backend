<?php

namespace App\Services;

use App\Models\Accommodation;
use App\Models\BlockedDate;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Check if accommodation is available for the given date range
     */
    public function checkAvailability(
        Accommodation $accommodation,
        string|Carbon $checkIn,
        string|Carbon $checkOut,
        ?string $excludeBookingId = null
    ): array {
        $checkIn = $checkIn instanceof Carbon ? $checkIn : Carbon::parse($checkIn);
        $checkOut = $checkOut instanceof Carbon ? $checkOut : Carbon::parse($checkOut);

        // Check if accommodation is bookable
        if (!$accommodation->isAvailable()) {
            return [
                'available' => false,
                'message' => "This accommodation is currently {$accommodation->status}.",
                'reason' => 'status',
            ];
        }

        // Validate dates
        if ($checkIn->isPast()) {
            return [
                'available' => false,
                'message' => 'Check-in date cannot be in the past.',
                'reason' => 'past_date',
            ];
        }

        if ($checkOut->lte($checkIn)) {
            return [
                'available' => false,
                'message' => 'Check-out date must be after check-in date.',
                'reason' => 'invalid_dates',
            ];
        }

        // Check minimum stay
        $nights = $checkIn->diffInDays($checkOut);
        if ($nights < $accommodation->minimum_stay) {
            return [
                'available' => false,
                'message' => "Minimum stay is {$accommodation->minimum_stay} night(s).",
                'reason' => 'minimum_stay',
                'minimum_stay' => $accommodation->minimum_stay,
                'requested_nights' => $nights,
            ];
        }

        // Check maximum stay
        if ($accommodation->maximum_stay && $nights > $accommodation->maximum_stay) {
            return [
                'available' => false,
                'message' => "Maximum stay is {$accommodation->maximum_stay} night(s).",
                'reason' => 'maximum_stay',
                'maximum_stay' => $accommodation->maximum_stay,
                'requested_nights' => $nights,
            ];
        }

        // Check for overlapping bookings
        $overlappingBookings = Booking::where('accommodation_id', $accommodation->id)
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('check_in_date', [$checkIn, $checkOut])
                    ->orWhereBetween('check_out_date', [$checkIn, $checkOut])
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where('check_in_date', '<=', $checkIn)
                          ->where('check_out_date', '>=', $checkOut);
                    });
            })
            ->when($excludeBookingId, function ($query, $id) {
                $query->where('id', '!=', $id);
            })
            ->exists();

        if ($overlappingBookings) {
            return [
                'available' => false,
                'message' => 'These dates are not available. Another booking exists.',
                'reason' => 'booking_conflict',
            ];
        }

        // Check for blocked dates
        $blockedDates = BlockedDate::where('accommodation_id', $accommodation->id)
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->whereBetween('start_date', [$checkIn, $checkOut])
                    ->orWhereBetween('end_date', [$checkIn, $checkOut])
                    ->orWhere(function ($q) use ($checkIn, $checkOut) {
                        $q->where('start_date', '<=', $checkIn)
                          ->where('end_date', '>=', $checkOut);
                    });
            })
            ->first();

        if ($blockedDates) {
            return [
                'available' => false,
                'message' => 'Some dates in this range are blocked.',
                'reason' => 'blocked_dates',
                'blocked_reason' => $blockedDates->reason,
                'blocked_from' => $blockedDates->start_date->toDateString(),
                'blocked_to' => $blockedDates->end_date->toDateString(),
            ];
        }

        return [
            'available' => true,
            'message' => 'Accommodation is available for the selected dates.',
            'nights' => $nights,
            'check_in' => $checkIn->toDateString(),
            'check_out' => $checkOut->toDateString(),
        ];
    }

    /**
     * Get unavailable dates for an accommodation within a date range
     */
    public function getUnavailableDates(
        Accommodation $accommodation,
        string|Carbon $from,
        string|Carbon $to
    ): array {
        $from = $from instanceof Carbon ? $from : Carbon::parse($from);
        $to = $to instanceof Carbon ? $to : Carbon::parse($to);

        $unavailableDates = [];

        // Get booked dates
        $bookings = Booking::where('accommodation_id', $accommodation->id)
            ->whereIn('status', ['pending', 'confirmed', 'checked_in'])
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('check_in_date', [$from, $to])
                    ->orWhereBetween('check_out_date', [$from, $to])
                    ->orWhere(function ($q) use ($from, $to) {
                        $q->where('check_in_date', '<=', $from)
                          ->where('check_out_date', '>=', $to);
                    });
            })
            ->get();

        foreach ($bookings as $booking) {
            $period = Carbon::parse($booking->check_in_date)
                ->daysUntil($booking->check_out_date);

            foreach ($period as $date) {
                $dateString = $date->format('Y-m-d');
                if (!in_array($dateString, $unavailableDates)) {
                    $unavailableDates[] = $dateString;
                }
            }
        }

        // Get blocked dates
        $blockedDates = BlockedDate::where('accommodation_id', $accommodation->id)
            ->where(function ($query) use ($from, $to) {
                $query->whereBetween('start_date', [$from, $to])
                    ->orWhereBetween('end_date', [$from, $to])
                    ->orWhere(function ($q) use ($from, $to) {
                        $q->where('start_date', '<=', $from)
                          ->where('end_date', '>=', $to);
                    });
            })
            ->get();

        foreach ($blockedDates as $blocked) {
            $period = Carbon::parse($blocked->start_date)
                ->daysUntil($blocked->end_date->addDay());

            foreach ($period as $date) {
                $dateString = $date->format('Y-m-d');
                if (!in_array($dateString, $unavailableDates)) {
                    $unavailableDates[] = $dateString;
                }
            }
        }

        sort($unavailableDates);
        return $unavailableDates;
    }

    /**
     * Get available date ranges for an accommodation
     */
    public function getAvailableDateRanges(
        Accommodation $accommodation,
        string|Carbon $from,
        string|Carbon $to,
        int $minimumNights = 1
    ): array {
        $from = $from instanceof Carbon ? $from : Carbon::parse($from);
        $to = $to instanceof Carbon ? $to : Carbon::parse($to);

        $unavailableDates = $this->getUnavailableDates($accommodation, $from, $to);
        $availableRanges = [];
        $currentRange = null;

        $period = $from->daysUntil($to->addDay());

        foreach ($period as $date) {
            $dateString = $date->format('Y-m-d');

            if (in_array($dateString, $unavailableDates)) {
                // Date is unavailable, close current range if exists
                if ($currentRange) {
                    $nights = Carbon::parse($currentRange['from'])->diffInDays($currentRange['to']);
                    if ($nights >= $minimumNights) {
                        $availableRanges[] = $currentRange;
                    }
                    $currentRange = null;
                }
            } else {
                // Date is available
                if (!$currentRange) {
                    $currentRange = ['from' => $dateString, 'to' => $dateString];
                } else {
                    $currentRange['to'] = $dateString;
                }
            }
        }

        // Close final range if exists
        if ($currentRange) {
            $nights = Carbon::parse($currentRange['from'])->diffInDays($currentRange['to']);
            if ($nights >= $minimumNights) {
                $availableRanges[] = $currentRange;
            }
        }

        return $availableRanges;
    }
}
