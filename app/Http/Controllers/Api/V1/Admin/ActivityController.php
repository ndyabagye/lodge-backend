<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActivityRequest;
use App\Http\Requests\Admin\UploadImagesRequest;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\ActivityImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Image as InterventionImage;

class ActivityController extends Controller
{
    /**
     * List all activities
     */
    public function index(Request $request): JsonResponse
    {
        $query = Activity::with('images');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $activities = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => ActivityResource::collection($activities),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * Create activity
     */
    public function store(ActivityRequest $request): JsonResponse
    {
        $activity = Activity::create($request->validated());

        return response()->json([
            'data' => new ActivityResource($activity->load('images')),
            'message' => 'Activity created successfully',
        ], 201);
    }

    /**
     * Update activity
     */
    public function update(ActivityRequest $request, string $id): JsonResponse
    {
        $activity = Activity::findOrFail($id);
        $activity->update($request->validated());

        return response()->json([
            'data' => new ActivityResource($activity->fresh('images')),
            'message' => 'Activity updated successfully',
        ]);
    }

    /**
     * Delete activity
     */
    public function destroy(string $id): JsonResponse
    {
        $activity = Activity::findOrFail($id);

        foreach ($activity->images as $image) {
            Storage::disk('public')->delete($image->url);
            if ($image->thumbnail_url) {
                Storage::disk('public')->delete($image->thumbnail_url);
            }
        }

        $activity->delete();

        return response()->json([
            'message' => 'Activity deleted successfully',
        ]);
    }

    /**
     * Upload images
     */
    public function uploadImages(UploadImagesRequest $request, string $id): JsonResponse
    {
        $activity = Activity::findOrFail($id);

        $images = [];
        $lastOrder = $activity->images()->max('order') ?? 0;

        foreach ($request->file('images') as $index => $file) {
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = "activities/{$activity->id}";

            $file->storeAs($path, $filename, 'public');

            $thumbnailFilename = 'thumb_' . $filename;
            $thumbnail = InterventionImage::make($file)->fit(400, 300);
            Storage::disk('public')->put(
                "{$path}/{$thumbnailFilename}",
                $thumbnail->encode()
            );

            $image = ActivityImage::create([
                'activity_id' => $activity->id,
                'url' => "storage/{$path}/{$filename}",
                'thumbnail_url' => "storage/{$path}/{$thumbnailFilename}",
                'alt_text' => $request->alt_texts[$index] ?? null,
                'caption' => $request->captions[$index] ?? null,
                'order' => ++$lastOrder,
                'is_featured' => $index === 0 && $activity->images()->count() === 0,
            ]);

            $images[] = $image;
        }

        return response()->json([
            'data' => $images,
            'message' => 'Images uploaded successfully',
        ], 201);
    }

    /**
     * Delete image
     */
    public function deleteImage(string $id, string $imageId): JsonResponse
    {
        $activity = Activity::findOrFail($id);
        $image = ActivityImage::where('activity_id', $id)->findOrFail($imageId);

        Storage::disk('public')->delete($image->url);
        if ($image->thumbnail_url) {
            Storage::disk('public')->delete($image->thumbnail_url);
        }

        $image->delete();

        return response()->json([
            'message' => 'Image deleted successfully',
        ]);
    }
}
