<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AccommodationRequest;
use App\Http\Requests\Admin\UploadImagesRequest;
use App\Http\Resources\AccommodationResource;
use App\Models\Accommodation;
use App\Models\AccommodationImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Image as InterventionImage;

class AccommodationController extends Controller
{
    /**
     * List all accommodations (paginated)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Accommodation::with(['images', 'amenities']);

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        $accommodations = $query->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => AccommodationResource::collection($accommodations),
            'meta' => [
                'current_page' => $accommodations->currentPage(),
                'last_page' => $accommodations->lastPage(),
                'per_page' => $accommodations->perPage(),
                'total' => $accommodations->total(),
            ],
        ]);
    }

    /**
     * Create accommodation
     */
    public function store(AccommodationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $accommodation = Accommodation::create($data);

        // Attach amenities
        if (isset($data['amenity_ids'])) {
            $accommodation->amenities()->sync($data['amenity_ids']);
        }

        return response()->json([
            'data' => new AccommodationResource($accommodation->load(['images', 'amenities'])),
            'message' => 'Accommodation created successfully',
        ], 201);
    }

    /**
     * Update accommodation
     */
    public function update(AccommodationRequest $request, string $id): JsonResponse
    {
        $accommodation = Accommodation::findOrFail($id);

        $data = $request->validated();
        $accommodation->update($data);

        // Sync amenities
        if (isset($data['amenity_ids'])) {
            $accommodation->amenities()->sync($data['amenity_ids']);
        }

        return response()->json([
            'data' => new AccommodationResource($accommodation->fresh(['images', 'amenities'])),
            'message' => 'Accommodation updated successfully',
        ]);
    }

    /**
     * Delete accommodation
     */
    public function destroy(string $id): JsonResponse
    {
        $accommodation = Accommodation::findOrFail($id);

        // Delete images from storage
        foreach ($accommodation->images as $image) {
            Storage::disk('public')->delete($image->url);
            if ($image->thumbnail_url) {
                Storage::disk('public')->delete($image->thumbnail_url);
            }
        }

        $accommodation->delete();

        return response()->json([
            'message' => 'Accommodation deleted successfully',
        ]);
    }

    /**
     * Upload images
     */
    public function uploadImages(UploadImagesRequest $request, string $id): JsonResponse
    {
        $accommodation = Accommodation::findOrFail($id);

        $images = [];
        $lastOrder = $accommodation->images()->max('order') ?? 0;

        foreach ($request->file('images') as $index => $file) {
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = "accommodations/{$accommodation->id}";

            // Store original
            $file->storeAs($path, $filename, 'public');

            // Create thumbnail
            $thumbnailFilename = 'thumb_' . $filename;
            $thumbnail = InterventionImage::make($file)->fit(400, 300);
            Storage::disk('public')->put(
                "{$path}/{$thumbnailFilename}",
                $thumbnail->encode()
            );

            $image = AccommodationImage::create([
                'accommodation_id' => $accommodation->id,
                'url' => "storage/{$path}/{$filename}",
                'thumbnail_url' => "storage/{$path}/{$thumbnailFilename}",
                'alt_text' => $request->alt_texts[$index] ?? null,
                'caption' => $request->captions[$index] ?? null,
                'order' => ++$lastOrder,
                'is_featured' => $index === 0 && $accommodation->images()->count() === 0,
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
        $accommodation = Accommodation::findOrFail($id);
        $image = AccommodationImage::where('accommodation_id', $id)
            ->findOrFail($imageId);

        // Delete from storage
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
