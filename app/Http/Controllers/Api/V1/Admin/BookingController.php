<?php

namespace App\Http\Controllers\Api\V1\Admin;

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBookingStatusRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private BookingService $bookingService
    ) {}

    /**
     * List all bookings (admin view)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Booking::with(['accommodation.images', 'user', 'payments']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->filled('from_date')) {
            $query->where('check_in_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('check_in_date', '<=', $request->to_date);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'ilike', "%{$search}%")
                  ->orWhere('guest_first_name', 'ilike', "%{$search}%")
                  ->orWhere('guest_last_name', 'ilike', "%{$search}%")
                  ->orWhere('guest_email', 'ilike', "%{$search}%");
            });
        }

        $bookings = $query->latest()->paginate($request->get('per_page', 20));

        return $this->paginatedResponse($bookings, BookingResource::class);
    }

    /**
     * Update booking status
     */
    public function updateStatus(UpdateBookingStatusRequest $request, Booking $booking): JsonResponse
    {
        try {
            $status = BookingStatus::from($request->status);

            $this->bookingService->updateBookingStatus($booking, $status);

            if ($request->filled('internal_notes')) {
                $booking->update([
                    'internal_notes' => $request->internal_notes
                ]);
            }

            return $this->resourceResponse(
                new BookingResource($booking->fresh()),
                'Booking status updated successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update status: ' . $e->getMessage(), 500);
        }
    }
}
