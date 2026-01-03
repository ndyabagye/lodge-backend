<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAccommodationRequest;
use App\Http\Requests\Admin\UpdateAccommodationRequest;
use App\Http\Requests\Admin\UploadImagesRequest;
use App\Http\Resources\AccommodationResource;
use App\Models\Accommodation;
use App\Models\AccommodationImage;
use App\Services\ImageService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccommodationController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ImageService $imageService
    ) {}

    /**
     * List all accommodations (admin view)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Accommodation::with(['images', 'amenities']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('type', 'ilike', "%{$search}%");
            });
        }

        $accommodations = $query->latest()->paginate($request->get('per_page', 15));

        return $this->paginatedResponse($accommodations, AccommodationResource::class);
    }

    /**
     * Create accommodation
     */
    public function store(StoreAccommodationRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $amenityIds = $data['amenity_ids'] ?? [];
            unset($data['amenity_ids']);

            DB::beginTransaction();

            $accommodation = Accommodation::create($data);

            if (! empty($amenityIds)) {
                $accommodation->amenities()->sync($amenityIds);
            }

            DB::commit();

            return $this->createdResponse(
                new AccommodationResource($accommodation->load(['images', 'amenities'])),
                'Accommodation created successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse('Failed to create accommodation: '.$e->getMessage(), 500);
        }
    }

    /**
     * Show accommodation
     */
    public function show(Accommodation $accommodation): JsonResponse
    {
        $accommodation->load([
            'images',
            'amenities',
            'bookings' => function ($query) {
                $query->latest()->limit(10);
            },
        ]);

        return $this->resourceResponse(new AccommodationResource($accommodation), 200);
    }

    /**
     * Update accommodation
     */
    public function update(UpdateAccommodationRequest $request, Accommodation $accommodation): JsonResponse
    {
        try {
            $data = $request->validated();
            $amenityIds = $data['amenity_ids'] ?? null;
            unset($data['amenity_ids']);

            DB::beginTransaction();

            $accommodation->update($data);

            if ($amenityIds !== null) {
                $accommodation->amenities()->sync($amenityIds);
            }

            DB::commit();

            return $this->resourceResponse(
                new AccommodationResource($accommodation->fresh(['images', 'amenities'])),
                'Accommodation updated successfully'
            );

        } catch (\Exception $e) {
            DB::rollBack();

            return $this->errorResponse('Failed to update accommodation: '.$e->getMessage(), 500);
        }
    }

    /**
     * Delete accommodation
     */
    public function destroy(Accommodation $accommodation): JsonResponse
    {
        $activeBookings = $accommodation->bookings()->active()->count();

        if ($activeBookings > 0) {
            return $this->errorResponse(
                'Cannot delete accommodation with active bookings',
                422
            );
        }

        $accommodation->delete();

        return $this->successResponse(null, 'Accommodation deleted successfully');
    }

    /**
     * Upload images for accommodation
     */
    public function uploadImages(UploadImagesRequest $request, Accommodation $accommodation): JsonResponse
    {
        try {
            $uploadedImages = [];
            $order = $accommodation->images()->max('order') ?? 0;

            DB::beginTransaction();

            foreach ($request->file('images') as $file) {
                $imageData = $this->imageService->uploadImage($file, 'accommodations');

                $image = $accommodation->images()->create([
                    'accommodation_id' => $accommodation->id,
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

            return $this->errorResponse('Failed to upload images: '.$e->getMessage(), 500);
        }
    }

    /**
     * Delete accommodation image
     */
    public function deleteImage(Accommodation $accommodation, AccommodationImage $image): JsonResponse
    {

        // \Log::info('Image debug:', [
        //     'accommodation_id' => $accommodation->id,
        //     'image_id' => $image->accommodation_id,
        // ]);

        if ($image->accommodation_id !== $accommodation->id) {
            return $this->errorResponse(
                'Image does not belong to this accommodation',
                422
            );
        }

        try {
            $this->imageService->deleteImage($image->url, $image->thumbnail_url);
            $image->delete();

            return $this->successResponse(null, 'Image deleted successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete image: '.$e->getMessage(), 500);
        }
    }
}
