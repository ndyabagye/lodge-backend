<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\ReviewResource;
use App\Models\Activity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
        if ($request->boolean('featured')) {
            $query->where('featured', true);
        }

        // Sort
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSorts = ['created_at', 'name', 'price', 'rating'];
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
    public function show(string $id): JsonResponse
    {
        $activity = Activity::with([
            'images',
            'reviews' => function ($query) {
                $query->where('status', 'approved')
                    ->with('user')
                    ->latest()
                    ->limit(10);
            }
        ])->findOrFail($id);

        return response()->json([
            'data' => new ActivityResource($activity),
        ]);
    }

    /**
     * Get activity by slug
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $activity = Activity::with([
            'images',
            'reviews' => function ($query) {
                $query->where('status', 'approved')
                    ->with('user')
                    ->latest()
                    ->limit(10);
            }
        ])->where('slug', $slug)->firstOrFail();

        return response()->json([
            'data' => new ActivityResource($activity),
        ]);
    }

    /**
     * Check activity availability for a date
     */
    public function checkAvailability(string $id, Request $request): JsonResponse
    {
        $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
        ]);

        $activity = Activity::findOrFail($id);

        // Basic availability check - can be extended
        $available = $activity->status === 'available';

        return response()->json([
            'data' => [
                'available' => $available,
                'message' => $available
                    ? 'Activity is available for booking'
                    : 'Activity is currently unavailable',
            ],
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
