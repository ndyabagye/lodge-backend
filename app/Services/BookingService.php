<?php

namespace App\Services;

use App\Models\Accommodation;
use App\Models\Booking;
// use App\Models\User;
use App\Notifications\BookingConfirmation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(
        private AvailabilityService $availabilityService,
        private PricingService $pricingService,
    ) {}

    /**
     * Create a new booking
     *     */
    public function createBooking(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            $accommodation = Accommodation::findOrFail(
                $data["accommodation_id"],
            );

            $checkIn = Carbon::parse($data["check_in_date"]);
            $checkOut = Carbon::parse($data["check_out_date"]);

            // Check availability
            $availability = $this->availabilityService->checkAvailability(
                $accommodation,
                $checkIn,
                $checkOut,
            );

            if (!$availability["available"]) {
                throw new \Exception($availability["message"]);
            }

            // Calculate pricing
            $pricing = $this->pricingService->calculatePrice(
                $accommodation,
                $checkIn,
                $checkOut,
                [
                    "discount" => $data["discount"] ?? 0,
                    "service_fee" => $data["service_fee"] ?? 0,
                ],
            );

            // Validate guest count
            if ($data["num_guests"] > $accommodation->max_guests) {
                throw new \Exception(
                    "Number of guests exceeds maximum allowed ({$accommodation->max_guests}).",
                );
            }

            // Create booking
            $booking = Booking::create([
                "user_id" => $data["user_id"] ?? null,
                "accommodation_id" => $accommodation->id,
                "check_in_date" => $checkIn,
                "check_out_date" => $checkOut,
                "num_guests" => $data["num_guests"],
                "num_adults" => $data["num_adults"] ?? $data["num_guests"],
                "num_children" => $data["num_children"] ?? 0,
                "subtotal" => $pricing["subtotal"],
                "tax_amount" => $pricing["tax_amount"],
                "service_fee" => $pricing["service_fee"],
                "cleaning_fee" => $pricing["cleaning_fee"],
                "discount" => $pricing["discount"],
                "total_amount" => $pricing["total_amount"],
                "payment_method" => $data["payment_method"] ?? null,
                "guest_first_name" => $data["guest_first_name"] ?? null,
                "guest_last_name" => $data["guest_last_name"] ?? null,
                "guest_email" => $data["guest_email"] ?? null,
                "guest_phone" => $data["guest_phone"] ?? null,
                "special_requests" => $data["special_requests"] ?? null,
                "status" => "pending",
                "payment_status" => "pending",
            ]);

            // Increment accommodation bookings count
            $accommodation->incrementBookings();

            // Send notification
            if ($booking->user) {
                $booking->user->notify(new BookingConfirmation($booking));
            }

            return $booking->fresh(["accommodation", "user"]);
        });
    }

    /**
     * Cancel a booking
     */
    public function cancelBooking(Booking $booking): bool
    {
        if (!$booking->canBeCancelled()) {
            throw new \Exception("This booking cannot be cancelled.");
        }

        return $booking->update(["status" => "cancelled"]);
    }

    /**
     * Update booking status
     */
    public function updateStatus(Booking $booking, string $status): bool
    {
        $validStatuses = [
            "pending",
            "confirmed",
            "checked_in",
            "checked_out",
            "cancelled",
        ];

        if (!in_array($status, $validStatuses)) {
            throw new \Exception("Invalid booking status.");
        }

        return $booking->update(["status" => $status]);
    }
}
