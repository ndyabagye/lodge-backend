<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActivityRequest;
use App\Http\Requests\Admin\StoreActivityRequest;
use App\Http\Requests\Admin\UpdateActivityRequest;
use App\Http\Requests\Admin\UploadImagesRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\ActivityImage;
use App\Services\ImageService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ImageService $imageService
    ) {}

    /**
     * List all activities (admin view)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Activity::with('images');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('category', 'ilike', "%{$search}%");
            });
        }

        $activities = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($activities, ActivityResource::class);
    }

    /**
     * Create activity
     */
    public function store(StoreActivityRequest $request): JsonResponse
    {
        $activity = Activity::create($request->validated());

        return $this->createdResponse(
            new ActivityResource($activity->load('images')),
            'Activity created successfully'
        );
    }

    /**
     * Show activity
     */
    public function show(Activity $activity): JsonResponse
    {
        $activity->load('images');

        return $this->resourceResponse(new ActivityResource($activity), 200);
    }

    /**
     * Update activity
     */
    public function update(UpdateActivityRequest $request, Activity $activity): JsonResponse
    {
        $activity->update($request->validated());

        return $this->resourceResponse(
            new ActivityResource($activity->fresh('images')),
            'Activity updated successfully'
        );
    }

    /**
     * Delete activity
     */
    public function destroy(Activity $activity): JsonResponse
    {
        $activity->delete();

        return $this->successResponse(null, 'Activity deleted successfully');
    }

    /**
     * Upload images for activity
     */
    public function uploadImages(UploadImagesRequest $request, Activity $activity): JsonResponse
    {
        try {
            $uploadedImages = [];
            $order = $activity->images()->max('order') ?? 0;

            DB::beginTransaction();

            foreach ($request->file('images') as $file) {
                $imageData = $this->imageService->uploadImage($file, 'activities');

                $image = $activity->images()->create([
                    'activity_id' => $activity->id,
                    'url' => $imageData['url'],
                    'thumbnail_url' => $imageData['thumbnail_url'],
                    'order' => ++$order,
                    'is_featured' => false,
                ]);

                $uploadedImages[] = $image;
            }

            DB::commit();

            return $this->successResponse(
                $uploadedImages,
                'Images uploaded successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to upload images: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Delete activity image
     */
    public function deleteImage(Activity $activity, ActivityImage $image): JsonResponse
    {
        if ($image->activity_id !== $activity->id) {
            return $this->errorResponse(
                'Image does not belong to this activity',
                422
            );
        }

        try {
            $this->imageService->deleteImage($image->url, $image->thumbnail_url);
            $image->delete();

            return $this->successResponse(null, 'Image deleted successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete image: ' . $e->getMessage(), 500);
        }
    }
}
