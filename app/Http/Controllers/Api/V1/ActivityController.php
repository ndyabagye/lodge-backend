<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\ReviewResource;
use App\Models\Activity;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\Action;

class ActivityController extends Controller
{
    use ApiResponse;

    /**
     * List all activities with filters
     */
    public function index(Request $request): JsonResponse
    {
        $query = Activity::with('images')->where('status', 'available');

        // Apply filters
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
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
                  ->orWhere('category', 'ilike', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $allowedSorts = ['name', 'price', 'rating', 'created_at', 'duration'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        $activities = $query->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($activities, ActivityResource::class);
    }

    /**
     * Get single activity
     */
    public function show(Activity $activity): JsonResponse
    {
        $activity->load([
            'images',
            'reviews' => function ($query) {
                $query->approved()->with('user')->latest()->limit(10);
            }
        ]);

        return $this->resourceResponse(new ActivityResource($activity), 200);
    }

    /**
     * Get activity by slug
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $activity = Activity::where('slug', $slug)
            ->with('images')
            ->firstOrFail();

        return $this->resourceResponse(new ActivityResource($activity), 200);
    }

    /**
     * Check activity availability for date
     */
    public function checkAvailability(Request $request, Activity $activity): JsonResponse
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'participants' => 'nullable|integer|min:1',
        ]);

        $available = $activity->isAvailable();

        if ($activity->max_participants && $request->filled('participants')) {
            if ($request->participants > $activity->max_participants) {
                $available = false;
                $message = "Maximum {$activity->max_participants} participants allowed";
            }
        }

        $message = $available
            ? 'Activity is available for the selected date'
            : ($message ?? 'Activity is currently not available');

        return $this->successResponse([
            'available' => $available,
            'message' => $message,
            'activity' => [
                'id' => $activity->id,
                'name' => $activity->name,
                'max_participants' => $activity->max_participants,
            ],
        ], 200);
    }
}
