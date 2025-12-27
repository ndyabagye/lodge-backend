<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CheckAvailabilityRequest;
use App\Http\Requests\Booking\CreateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Accommodation;
use App\Models\Booking;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        private BookingService $bookingService,
        private AvailabilityService $availabilityService,
        private PricingService $pricingService
    ) {}

    /**
     * List user's bookings (or all for admin)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Booking::with(['accommodation.images', 'payments']);

        // Admin can see all bookings, users see only their own
        if (!$user->isAdmin() && !$user->isStaff()) {
            $query->where('user_id', $user->id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Search by booking number or guest name
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'like', "%{$search}%")
                    ->orWhere('guest_first_name', 'like', "%{$search}%")
                    ->orWhere('guest_last_name', 'like', "%{$search}%")
                    ->orWhere('guest_email', 'like', "%{$search}%");
            });
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->where('check_in_date', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->where('check_out_date', '<=', $request->to_date);
        }

        $bookings = $query->latest()->paginate($request->get('per_page', 15));

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
     * Create a new booking
     */
    public function store(CreateBookingRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['user_id'] = $request->user()?->id;

            $booking = $this->bookingService->createBooking($data);

            return response()->json([
                'data' => new BookingResource($booking),
                'message' => 'Booking created successfully',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get single booking
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $booking = Booking::with(['accommodation.images', 'user', 'payments'])
            ->findOrFail($id);

        // Check authorization
        if (!$user->isAdmin() && !$user->isStaff() && $booking->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'data' => new BookingResource($booking),
        ]);
    }

    /**
     * Update booking (limited fields)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $booking = Booking::findOrFail($id);

        // Check authorization
        if (!$user->isAdmin() && !$user->isStaff() && $booking->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $request->validate([
            'special_requests' => ['sometimes', 'string', 'max:1000'],
            'guest_phone' => ['sometimes', 'string', 'max:20'],
        ]);

        $booking->update($request->only(['special_requests', 'guest_phone']));

        return response()->json([
            'data' => new BookingResource($booking->fresh(['accommodation', 'payments'])),
            'message' => 'Booking updated successfully',
        ]);
    }

    /**
     * Cancel booking
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $booking = Booking::findOrFail($id);

        // Check authorization
        if (!$user->isAdmin() && !$user->isStaff() && $booking->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $this->bookingService->cancelBooking($booking);

            return response()->json([
                'message' => 'Booking cancelled successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Check availability and get pricing
     */
    public function checkAvailability(CheckAvailabilityRequest $request): JsonResponse
    {
        $request->validate([
            'accommodation_id' => ['required', 'uuid', 'exists:accommodations,id'],
        ]);

        $accommodation = Accommodation::findOrFail($request->accommodation_id);

        $checkIn = Carbon::parse($request->start_date);
        $checkOut = Carbon::parse($request->end_date);

        // Check availability
        $availability = $this->availabilityService->checkAvailability(
            $accommodation,
            $checkIn,
            $checkOut
        );

        // If available, calculate pricing
        $pricing = null;
        if ($availability['available']) {
            $pricing = $this->pricingService->calculatePrice(
                $accommodation,
                $checkIn,
                $checkOut
            );
        }

        return response()->json([
            'data' => [
                'available' => $availability['available'],
                'message' => $availability['message'],
                'pricing' => $pricing,
            ],
        ]);
    }

    /**
     * Download booking invoice (PDF)
     */
    public function invoice(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $booking = Booking::with(['accommodation', 'user', 'payments'])
            ->findOrFail($id);

        // Check authorization
        if (!$user->isAdmin() && !$user->isStaff() && $booking->user_id !== $user->id) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // TODO: Generate PDF invoice
        // For now, return booking data
        return response()->json([
            'data' => new BookingResource($booking),
            'message' => 'PDF generation to be implemented',
        ]);
    }
}
