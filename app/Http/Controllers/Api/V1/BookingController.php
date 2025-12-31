<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CheckAvailabilityRequest;
use App\Http\Requests\Booking\CreateBookingRequest;
use App\Http\Requests\Booking\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Accommodation;
use App\Models\Booking;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use App\Services\PricingService;
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
     * List user's bookings
     */
    public function index(Request $request): JsonResponse
    {
        $query = Booking::with(['accommodation.images', 'payments'])
            ->where('user_id', $request->user()->id);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('from_date')) {
            $query->where('check_in_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('check_in_date', '<=', $request->to_date);
        }

        $bookings = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($bookings, BookingResource::class);
    }

    /**
     * Create new booking
     */
    public function store(CreateBookingRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['user_id'] = $request->user()?->id;

            $booking = $this->bookingService->createBooking($data);

            return $this->createdResponse(
                new BookingResource($booking),
                'Booking created successfully'
            );

        } catch (BookingException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->errorResponse('Booking failed: '.$e->getMessage(), 500);
        }
    }

    /**
     * Get single booking
     */
    public function show(Request $request, Booking $booking): JsonResponse
    {
        // Authorization check
        if ($booking->user_id !== $request->user()->id && ! $request->user()->isStaff()) {
            return $this->forbiddenResponse('You do not have access to this booking');
        }

        $booking->load(['accommodation.images', 'payments', 'user']);

        return $this->resourceResponse(new BookingResource($booking));
    }

    /**
     * Update booking
     */
    public function update(UpdateBookingRequest $request, Booking $booking): JsonResponse
    {
        try {
            $booking = $this->bookingService->updateBooking($booking, $request->validated());

            return $this->resourceResponse(
                new BookingResource($booking),
                'Booking updated successfully'
            );

        } catch (BookingException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Cancel booking
     */
    public function destroy(Request $request, Booking $booking): JsonResponse
    {
        // Authorization check
        if ($booking->user_id !== $request->user()->id && ! $request->user()->isStaff()) {
            return $this->forbiddenResponse('You do not have permission to cancel this booking');
        }

        try {
            $result = $this->bookingService->cancelBooking(
                $booking,
                $request->input('reason')
            );

            return $this->successResponse(
                [
                    'booking' => new BookingResource($result['booking']),
                    'refund_info' => $result['refund_info'],
                ],
                'Booking cancelled successfully'
            );

        } catch (BookingException $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Check availability and calculate price
     */
    public function checkAvailability(CheckAvailabilityRequest $request): JsonResponse
    {
        $accommodation = Accommodation::findOrFail($request->accommodation_id);

        $availability = $this->availabilityService->checkAvailability(
            $accommodation,
            $request->check_in_date,
            $request->check_out_date
        );

        if (! $availability['available']) {
            return $this->errorResponse($availability['message'], 422, $availability);
        }

        $pricing = $this->pricingService->calculateBookingPrice(
            $accommodation,
            $request->check_in_date,
            $request->check_out_date,
            $request->input('discount', 0)
        );

        return $this->successResponse([
            'available' => true,
            'accommodation' => [
                'id' => $accommodation->id,
                'name' => $accommodation->name,
                'max_guests' => $accommodation->max_guests,
            ],
            'availability' => $availability,
            'pricing' => $pricing,
        ]);
    }

    /**
     * Download booking invoice (PDF)
     */
    public function downloadInvoice(Request $request, Booking $booking)
    {
        // Authorization check
        if ($booking->user_id !== $request->user()->id && ! $request->user()->isStaff()) {
            return $this->forbiddenResponse('You do not have access to this invoice');
        }

        $invoiceService = app(\App\Services\InvoiceService::class);

        return $invoiceService->downloadInvoice($booking);
    }
}
