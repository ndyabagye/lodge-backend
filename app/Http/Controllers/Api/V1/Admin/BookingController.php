<?php

namespace App\Http\Controllers\Api\V1\Admin;

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateBookingStatusRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        private BookingService $bookingService
    ) {}

    /**
     * List all bookings with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Booking::with(['accommodation.images', 'user', 'payments']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'like', "%{$search}%")
                    ->orWhere('guest_first_name', 'like', "%{$search}%")
                    ->orWhere('guest_last_name', 'like', "%{$search}%")
                    ->orWhere('guest_email', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('check_in_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('check_out_date', '<=', $request->to_date);
        }

        $bookings = $query->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    /**
     * Update booking status
     */
    public function updateStatus(UpdateBookingStatusRequest $request, string $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        try {
            $this->bookingService->updateStatus($booking, $request->status);

            if ($request->has('internal_notes')) {
                $booking->update(['internal_notes' => $request->internal_notes]);
            }

            return response()->json([
                'data' => new BookingResource($booking->fresh(['accommodation', 'user', 'payments'])),
                'message' => 'Booking status updated successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
