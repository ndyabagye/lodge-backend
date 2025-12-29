<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\ReviewResource;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\Action;

class ActivityController extends Controller
{
    /**
     * List all activities with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Activity::with(['images', 'featuredImage'])
            ->where('status', 'available');

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereFullText(['name', 'short_description', 'description'], $search);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by price range
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Filter featured
        if ($request->has('featured') && $request->featured === 'true') {
            $query->where('featured', true);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('description', 'ilike', "%{$search}%")
                  ->orWhere('category', 'ilike', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSorts = ['name', 'price', 'rating', 'created_at', 'duration'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $activities = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => ActivityResource::collection($activities),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
            'links' => [
                'first' => $activities->url(1),
                'last' => $activities->url($activities->lastPage()),
                'prev' => $activities->previousPageUrl(),
                'next' => $activities->nextPageUrl(),
            ],
        ]);
    }

    /**
     * Get single activity by ID
     */
    public function show(Activity $activity): JsonResponse
    {
        $activity->load(['images', 'reviews' => function ($query) {
            $query->approved()->with('user')->latest()->limit(10);
        }]);

        return response()->json([
            'data' => new ActivityResource($activity)
        ]);
    }

    /**
     * Get activity by slug
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $activity = Activity::where('slug', $slug)
            ->with([
            'images',
            'reviews' => function ($query) {
                $query->where('status', 'approved')
                    ->with('user')
                    ->latest()
                    ->limit(10);
            }
        ])
            ->firstOrFail();

        return response()->json([
            'data' => new ActivityResource($activity),
        ]);
    }

    /**
     * Check activity availability for a date
     */
    public function checkAvailability(Request $request, Activity $activity): JsonResponse
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'participants' => 'nullable|integer|min:1',
        ]);

        // Simple availability check
        $available = $activity->isAvailable();

        if ($activity->max_participants && $request->participants) {
            $available = $available && $request->participants <= $activity->max_participants;
        }

        return response()->json([
            'data' => [
                'available' => $available,
                'activity' => new ActivityResource($activity),
                'message' => $available
                    ? 'Activity is available for the selected date'
                    : 'Activity is not available',
            ]
        ]);
    }

    /**
     * Get activity reviews
     */
    public function reviews(string $id): JsonResponse
    {
        $activity = Activity::findOrFail($id);

        $reviews = $activity->reviews()
            ->where('status', 'approved')
            ->with('user')
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => ReviewResource::collection($reviews),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'average_rating' => round($activity->rating, 2),
            ],
        ]);
    }
}
