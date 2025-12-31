<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\BookingException;
use App\Models\Accommodation;
use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingCancelled;
use App\Notifications\BookingNotification;
use App\Notifications\BookingConfirmation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class BookingService
{
    public function __construct(
        private AvailabilityService $availabilityService,
        private PricingService $pricingService,
    ) {}

    /**
     * Create a new booking
     */
    public function createBooking(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            $accommodation = Accommodation::findOrFail($data['accommodation_id']);
            $checkIn = Carbon::parse($data['check_in_date']);
            $checkOut = Carbon::parse($data['check_out_date']);

            // Verify availability
            $availability = $this->availabilityService->checkAvailability(
                $accommodation,
                $checkIn,
                $checkOut
            );

            if (!$availability['available']) {
                throw BookingException::unavailable($availability['message']);
            }

            // Validate guest count
            if ($data['num_guests'] > $accommodation->max_guests) {
                throw BookingException::exceedsCapacity($accommodation->max_guests);
            }

            // Calculate pricing
            $pricing = $this->pricingService->calculateBookingPrice(
                $accommodation,
                $checkIn,
                $checkOut,
                $data['discount'] ?? 0
            );

            // Create booking
            $booking = Booking::create([
                'user_id' => $data['user_id'] ?? null,
                'accommodation_id' => $accommodation->id,
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'num_guests' => $data['num_guests'],
                'num_adults' => $data['num_adults'] ?? $data['num_guests'],
                'num_children' => $data['num_children'] ?? 0,
                'subtotal' => $pricing['subtotal'],
                'cleaning_fee' => $pricing['cleaning_fee'],
                'service_fee' => $pricing['service_fee'],
                'tax_amount' => $pricing['tax_amount'],
                'discount' => $pricing['discount'],
                'total_amount' => $pricing['total_amount'],
                'payment_status' => PaymentStatus::PENDING,
                'payment_method' => $data['payment_method'] ?? null,
                'status' => BookingStatus::PENDING,
                'guest_first_name' => $data['guest_first_name'],
                'guest_last_name' => $data['guest_last_name'],
                'guest_email' => $data['guest_email'],
                'guest_phone' => $data['guest_phone'],
                'special_requests' => $data['special_requests'] ?? null,
            ]);

            // Update accommodation stats
            $accommodation->incrementBookings();

            // Send notifications
            $this->sendBookingNotifications($booking);

            return $booking->load(['accommodation.images', 'user']);
        });
    }

    /**
     * Update booking
     */
    public function updateBooking(Booking $booking, array $data): Booking
    {
        if (!$booking->isCancellable()) {
            throw BookingException::notModifiable();
        }

        DB::transaction(function () use ($booking, $data) {
            // If dates are being updated, recalculate pricing
            if (isset($data['check_in_date']) || isset($data['check_out_date'])) {
                $checkIn = isset($data['check_in_date'])
                    ? Carbon::parse($data['check_in_date'])
                    : $booking->check_in_date;

                $checkOut = isset($data['check_out_date'])
                    ? Carbon::parse($data['check_out_date'])
                    : $booking->check_out_date;

                // Verify new dates are available
                $availability = $this->availabilityService->checkAvailability(
                    $booking->accommodation,
                    $checkIn,
                    $checkOut,
                    $booking->id
                );

                if (!$availability['available']) {
                    throw BookingException::unavailable($availability['message']);
                }

                // Recalculate pricing
                $pricing = $this->pricingService->calculateBookingPrice(
                    $booking->accommodation,
                    $checkIn,
                    $checkOut,
                    $booking->discount
                );

                $data = array_merge($data, [
                    'subtotal' => $pricing['subtotal'],
                    'cleaning_fee' => $pricing['cleaning_fee'],
                    'service_fee' => $pricing['service_fee'],
                    'tax_amount' => $pricing['tax_amount'],
                    'total_amount' => $pricing['total_amount'],
                ]);
            }

            $booking->update($data);
        });

        return $booking->fresh(['accommodation.images', 'user']);
    }

    /**
     * Update booking status
     */
    public function updateBookingStatus(Booking $booking, BookingStatus $status): Booking
    {
        $oldStatus = $booking->status;
        $booking->update(['status' => $status]);

        // Send notification based on status change
        if ($status === BookingStatus::CONFIRMED && $oldStatus === BookingStatus::PENDING) {
            Notification::route('mail', $booking->guest_email)
                ->notify(new BookingConfirmation($booking));
        }

        return $booking->fresh();
    }

    /**
     * Cancel booking
     */
    public function cancelBooking(Booking $booking, ?string $reason = null): array
    {
        if (!$booking->isCancellable()) {
            throw BookingException::notCancellable();
        }

        $refundInfo = null;

        DB::transaction(function () use ($booking, $reason, &$refundInfo) {
            // Calculate refund if booking was paid
            if ($booking->isPaid()) {
                $refundInfo = $this->pricingService->calculateRefundAmount(
                    $booking->total_amount,
                    $booking->check_in_date
                );
            }

            $booking->update([
                'status' => BookingStatus::CANCELLED,
                'internal_notes' => $booking->internal_notes
                    ? $booking->internal_notes . "\n\nCancellation reason: " . ($reason ?? 'No reason provided')
                    : "Cancellation reason: " . ($reason ?? 'No reason provided'),
            ]);

            // Send cancellation notification
            Notification::route('mail', $booking->guest_email)
                ->notify(new BookingCancelled($booking, $reason));
        });

        return [
            'booking' => $booking->fresh(),
            'refund_info' => $refundInfo,
        ];
    }

    /**
     * Send booking notifications
     */
    private function sendBookingNotifications(Booking $booking): void
    {
        // Notify guest
        Notification::route('mail', $booking->guest_email)
            ->notify(new BookingConfirmation($booking));

        // Notify admins
        $admins = User::where('role', 'admin')->get();
        if ($admins->isNotEmpty()) {
            Notification::send($admins, new BookingNotification($booking));
        }
    }

    /**
     * Get booking statistics
     */
    public function getBookingStats(Carbon $from, Carbon $to): array
    {
        $bookings = Booking::whereBetween('check_in_date', [$from, $to])->get();

        $confirmedBookings = $bookings->where('status', BookingStatus::CONFIRMED);
        $paidBookings = $bookings->where('payment_status', PaymentStatus::PAID);

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'total_bookings' => $bookings->count(),
            'confirmed_bookings' => $confirmedBookings->count(),
            'pending_bookings' => $bookings->where('status', BookingStatus::PENDING)->count(),
            'cancelled_bookings' => $bookings->where('status', BookingStatus::CANCELLED)->count(),
            'total_revenue' => round($paidBookings->sum('total_amount'), 2),
            'pending_revenue' => round($bookings->where('payment_status', PaymentStatus::PENDING)->sum('total_amount'), 2),
            'average_booking_value' => $bookings->count() > 0 ? round($bookings->avg('total_amount'), 2) : 0,
            'total_nights' => $bookings->sum(function ($booking) {
                return $booking->nights;
            }),
        ];
    }
}

