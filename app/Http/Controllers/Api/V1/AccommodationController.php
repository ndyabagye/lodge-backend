<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccommodationResource;
use App\Http\Resources\ReviewResource;
use App\Models\Accommodation;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccommodationController extends Controller
{
    public function __construct(
        private AvailabilityService $availabilityService
    ) {}

    /**
     * List all accommodations with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Accommodation::with(['images', 'featuredImage', 'amenities'])
            ->where('status', 'available');

        // Search by name or description
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereFullText(['name', 'short_description', 'description'], $search);
        }

        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('base_price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('base_price', '<=', $request->max_price);
        }

        // Filter by minimum guests
        if ($request->has('min_guests')) {
            $query->where('max_guests', '>=', $request->min_guests);
        }

        // Filter featured
        if ($request->boolean('featured')) {
            $query->where('featured', true);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSorts = ['created_at', 'name', 'base_price', 'rating', 'bookings'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $accommodations = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => AccommodationResource::collection($accommodations),
            'meta' => [
                'current_page' => $accommodations->currentPage(),
                'last_page' => $accommodations->lastPage(),
                'per_page' => $accommodations->perPage(),
                'total' => $accommodations->total(),
            ],
            'links' => [
                'first' => $accommodations->url(1),
                'last' => $accommodations->url($accommodations->lastPage()),
                'prev' => $accommodations->previousPageUrl(),
                'next' => $accommodations->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get single accommodation by ID
     */
    public function show(Accommodation $accommodation): JsonResponse
    {
        // Increment views
        $accommodation->incrementViews();

        $accommodation->load(['images', 'amenities', 'reviews' => function ($query) {
            $query->approved()->with('user')->latest()->limit(10);
        }]);

        return response()->json([
            'data' => new AccommodationResource($accommodation),
        ]);
    }

    /**
     * Get accommodation by slug
     */
    public function showBySlug(string $slug): JsonResponse
    {

        $accommodation = Accommodation::where('slug', $slug)
            ->with(['images', 'amenities', 'reviews' => function ($query) {
                $query->approved()->with('user')->latest()->limit(10);
            }])->firstOrFail();

        $accommodation->incrementViews();

        return response()->json([
            'data' => new AccommodationResource($accommodation),
        ]);
    }

    /**
     * Check accommodation availability
     */
    public function checkAvailability(Request $request, Accommodation $accommodation): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
        ]);

        $checkIn = Carbon::parse($request->start_date);
        $checkOut = Carbon::parse($request->end_date);

        $availability = $this->availabilityService->checkAvailability(
            $accommodation,
            $checkIn,
            $checkOut
        );

        // Get unavailable dates for calendar
        $unavailableDates = $this->availabilityService->getUnavailableDates(
            $accommodation,
            $checkIn->copy()->startOfMonth(),
            $checkIn->copy()->addMonths(2)->endOfMonth()
        );

        return response()->json([
            'data' => array_merge($availability, [
                'unavailable_dates' => $unavailableDates,
            ]),
        ]);
    }

    /**
     * Get accommodation reviews
     */
    public function reviews(Accommodation $accommodation): JsonResponse
    {
        $reviews = $accommodation->reviews()
            ->approved()
            ->with('user')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => ReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'average_rating' => $accommodation->rating,
            ]
        ]);
    }
}
