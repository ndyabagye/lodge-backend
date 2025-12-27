<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Accommodation extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        "name",
        "slug",
        "type",
        "description",
        "short_description",
        "max_guests",
        "num_bedrooms",
        "num_bathrooms",
        "num_beds",
        "size_sqft",
        "base_price",
        "weekend_price",
        "cleaning_fee",
        "minimum_stay",
        "maximum_stay",
        "check_in_time",
        "check_out_time",
        "status",
        "featured",
        "rating",
        "views",
        "bookings",
    ];

    protected function casts(): array
    {
        return [
            "base_price" => "decimal:2",
            "weekend_price" => "decimal:2",
            "cleaning_fee" => "decimal:2",
            "rating" => "decimal:2",
            "featured" => "boolean",
            "check_in_time" => "datetime:H:i",
            "check_out_time" => "datetime:H:i",
        ];
    }

    /**
     * Get the images associated with the accommodation.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images(): HasMany
    {
        return $this->hasMany(AccommodationImage::class)->orderBy("order");
    }

    /**
     * Get the featured image associated with the accommodation.
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function featuredImage(): HasOne
    {
        return $this->hasMany(AccommodationImage::class)
            ->where("is_featured", true)
            ->limit(1);
    }

    /**
     * Get the amenities associated with the accommodation.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function amenities(): BelongsToMany
    {
        return $this->belongsToMany(Amenity::class);
    }

    /**
     * Get the bookings associated with the accommodation.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the blocked dates associated with the accommodation.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function blockedDates(): HasMany
    {
        return $this->hasMany(BlockedDate::class);
    }

    /**
     * Get the reviews associated with the accommodation.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Increment the view count of the accommodation.
     */
    public function incrementViews(): void
    {
        $this->increment("views");
    }

    /**
     * Increment the booking count of the accommodation.
     */
    public function incrementBookings(): void
    {
        $this->increment("bookings");
    }

    /**
     * Update the rating of the accommodation.
     */
    public function updateRating(): void
    {
        $avgRating = $this->reviews()
            ->where("status", "approved")
            ->avg("rating");

        $this->update(["rating" => round($avgRating, 2)]);
    }

    /**
     * Scope query to get featured accommodations.
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeFeatured($query)
    {
        return $query->where("featured", true);
    }

    /**
     * Scope query to get available accommodations.
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeAvailable($query)
    {
        return $query->where("status", "available");
    }
}
