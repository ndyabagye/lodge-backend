<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageService
{
    protected ImageManager $manager;

    public function __construct()
    {
        // Initialize Intervention Image with GD driver
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Upload and process image
     */
    public function uploadImage(UploadedFile $file, string $directory = 'accommodations'): array
    {
        // Generate unique filename
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = "{$directory}/{$filename}";
        $thumbnailPath = "{$directory}/thumbnails/{$filename}";

        // Store original image
        $storedPath = $file->storeAs('public/' . $directory, $filename);

        if (!$storedPath) {
            throw new \Exception('Failed to store image');
        }

        // Create thumbnail
        try {
            $image = $this->manager->read($file->getRealPath());

            // Resize to thumbnail size
            $image->cover(400, 300);

            // Save thumbnail
            $thumbnailFullPath = storage_path('app/public/' . $thumbnailPath);

            // Ensure thumbnails directory exists
            $thumbnailDir = dirname($thumbnailFullPath);
            if (!is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }

            $image->save($thumbnailFullPath, quality: 80);

        } catch (\Exception $e) {
            // If thumbnail creation fails, log but don't fail the upload
            \Log::warning('Thumbnail creation failed', [
                'error' => $e->getMessage(),
                'file' => $filename,
            ]);

            $thumbnailPath = null;
        }

        return [
            'url' => Storage::url($path),
            'thumbnail_url' => $thumbnailPath ? Storage::url($thumbnailPath) : Storage::url($path),
        ];
    }

    /**
     * Delete image and its thumbnail
     */
    public function deleteImage(string $url, ?string $thumbnailUrl = null): bool
    {
        try {
            // Convert URL to storage path
            $path = str_replace('/storage/', 'public/', parse_url($url, PHP_URL_PATH));

            // Delete original
            if (Storage::exists($path)) {
                Storage::delete($path);
            }

            // Delete thumbnail if exists
            if ($thumbnailUrl) {
                $thumbnailPath = str_replace('/storage/', 'public/', parse_url($thumbnailUrl, PHP_URL_PATH));
                if (Storage::exists($thumbnailPath)) {
                    Storage::delete($thumbnailPath);
                }
            }

            return true;

        } catch (\Exception $e) {
            \Log::error('Image deletion failed', [
                'error' => $e->getMessage(),
                'url' => $url,
            ]);

            return false;
        }
    }

    /**
     * Upload multiple images
     */
    public function uploadMultiple(array $files, string $directory = 'accommodations'): array
    {
        $uploaded = [];

        foreach ($files as $file) {
            try {
                $uploaded[] = $this->uploadImage($file, $directory);
            } catch (\Exception $e) {
                \Log::error('Multiple image upload failed for one file', [
                    'error' => $e->getMessage(),
                    'file' => $file->getClientOriginalName(),
                ]);

                // Continue with other files
                continue;
            }
        }

        return $uploaded;
    }

    /**
     * Optimize existing image
     */
    public function optimizeImage(string $path, int $quality = 85): bool
    {
        try {
            $fullPath = storage_path('app/public/' . str_replace('public/', '', $path));

            if (!file_exists($fullPath)) {
                return false;
            }

            $image = $this->manager->read($fullPath);
            $image->save($fullPath, quality: $quality);

            return true;

        } catch (\Exception $e) {
            \Log::error('Image optimization failed', [
                'error' => $e->getMessage(),
                'path' => $path,
            ]);

            return false;
        }
    }

    /**
     * Resize image to specific dimensions
     */
    public function resizeImage(string $path, int $width, int $height, bool $maintainAspectRatio = true): bool
    {
        try {
            $fullPath = storage_path('app/public/' . str_replace('public/', '', $path));

            if (!file_exists($fullPath)) {
                return false;
            }

            $image = $this->manager->read($fullPath);

            if ($maintainAspectRatio) {
                $image->scale(width: $width, height: $height);
            } else {
                $image->resize($width, $height);
            }

            $image->save($fullPath);

            return true;

        } catch (\Exception $e) {
            \Log::error('Image resize failed', [
                'error' => $e->getMessage(),
                'path' => $path,
            ]);

            return false;
        }
    }

    /**
     * Get image dimensions
     */
    public function getImageDimensions(string $path): ?array
    {
        try {
            $fullPath = storage_path('app/public/' . str_replace('public/', '', $path));

            if (!file_exists($fullPath)) {
                return null;
            }

            $image = $this->manager->read($fullPath);

            return [
                'width' => $image->width(),
                'height' => $image->height(),
            ];

        } catch (\Exception $e) {
            \Log::error('Failed to get image dimensions', [
                'error' => $e->getMessage(),
                'path' => $path,
            ]);

            return null;
        }
    }

    /**
     * Create watermarked image
     */
    public function addWatermark(string $path, string $watermarkPath, string $position = 'bottom-right'): bool
    {
        try {
            $fullPath = storage_path('app/public/' . str_replace('public/', '', $path));
            $fullWatermarkPath = storage_path('app/public/' . str_replace('public/', '', $watermarkPath));

            if (!file_exists($fullPath) || !file_exists($fullWatermarkPath)) {
                return false;
            }

            $image = $this->manager->read($fullPath);
            $watermark = $this->manager->read($fullWatermarkPath);

            // Resize watermark to 20% of image width
            $watermarkWidth = (int)($image->width() * 0.2);
            $watermark->scale(width: $watermarkWidth);

            // Position watermark
            $positions = [
                'bottom-right' => ['right', 'bottom'],
                'bottom-left' => ['left', 'bottom'],
                'top-right' => ['right', 'top'],
                'top-left' => ['left', 'top'],
            ];

            $pos = $positions[$position] ?? $positions['bottom-right'];

            $image->place($watermark, $pos[0], $pos[1], 10, 10);
            $image->save($fullPath);

            return true;

        } catch (\Exception $e) {
            \Log::error('Watermark addition failed', [
                'error' => $e->getMessage(),
                'path' => $path,
            ]);

            return false;
        }
    }
}
