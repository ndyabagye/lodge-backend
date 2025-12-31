<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccommodationResource;
use App\Http\Resources\ReviewResource;
use App\Models\Accommodation;
use App\Services\AvailabilityService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccommodationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AvailabilityService $availabilityService
    ) {}

    /**
     * List all accommodations with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Accommodation::with(['images', 'amenities'])
            ->where('status', 'available');

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('min_price')) {
            $query->where('base_price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('base_price', '<=', $request->max_price);
        }

        if ($request->filled('min_guests')) {
            $query->where('max_guests', '>=', $request->min_guests);
        }

        if ($request->filled('featured') && $request->featured === 'true') {
            $query->where('featured', true);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('type', 'ilike', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSorts = ['name', 'base_price', 'rating', 'created_at', 'views', 'bookings'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $accommodations = $query->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($accommodations, AccommodationResource::class);
    }

    /**
     * Get single accommodation by ID
     */
    public function show(Accommodation $accommodation): JsonResponse
    {
        // Increment views
        $accommodation->incrementViews();

        $accommodation->load([
            'images',
            'amenities',
            'reviews' => function ($query) {
                $query->approved()->with('user')->latest()->limit(10);
            },
        ]);

        return $this->resourceResponse(new AccommodationResource($accommodation), 200);
    }

    /**
     * Get accommodation by slug
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $accommodation = Accommodation::where('slug', $slug)
            ->with(['images', 'amenities'])
            ->firstOrFail();

        $accommodation->incrementViews();

        return $this->resourceResponse(new AccommodationResource($accommodation), 200);
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

        $availability = $this->availabilityService->checkAvailability(
            $accommodation,
            $request->start_date,
            $request->end_date
        );

        // Get unavailable dates for calendar (2 months window)
        $from = Carbon::parse($request->start_date)->startOfMonth();
        $to = $from->copy()->addMonths(2)->endOfMonth();

        $unavailableDates = $this->availabilityService->getUnavailableDates(
            $accommodation,
            $from,
            $to
        );

        return $this->successResponse(
            array_merge($availability, [
                'accommodation' => [
                    'id' => $accommodation->id,
                    'name' => $accommodation->name,
                    'max_guests' => $accommodation->max_guests,
                ],
                'unavailable_dates' => $unavailableDates,
            ])
        , 200);
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

        $response = $this->paginatedResponse($reviews, ReviewResource::class);

        // Add additional meta data
        $data = $response->getData(true);
        $data['meta']['average_rating'] = (float) $accommodation->rating;
        $data['meta']['total_reviews'] = $accommodation->reviews()->approved()->count();

        return response()->json($data);
    }
}
