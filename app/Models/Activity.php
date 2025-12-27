<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Activity extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        "name",
        "slug",
        "category",
        "description",
        "short_description",
        "duration",
        "price",
        "adult_price",
        "child_price",
        "group_price",
        "max_participants",
        "min_age",
        "max_age",
        "requirements",
        "safety_info",
        "included",
        "excluded",
        "status",
        "featured",
        "rating",
    ];

    protected function casts(): array
    {
        return [
            "price" => "decimal:2",
            "adult_price" => "decimal:2",
            "child_price" => "decimal:2",
            "group_price" => "decimal:2",
            "rating" => "decimal:2",
            "featured" => "boolean",
        ];
    }

    /**
     * Get the images associated with the activity.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function images(): HasMany
    {
        return $this->hasMany(ActivityImage::class)->orderBy("order");
    }

    /**
     * Get the featured image associated with the activity.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function featuredImage(): HasMany
    {
        return $this->hasMany(ActivityImage::class)
            ->where("is_featured", true)
            ->limit(1);
    }

    /**
     * Get the reviews associated with the activity.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Update the rating of the activity.
     */
    public function updateRating(): void
    {
        $avgRating = $this->reviews()
            ->where("status", "approved")
            ->avg("rating");

        $this->update(["rating" => round($avgRating, 2)]);
    }

    /**
     * Scope to filter featured activities.
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeFeatured($query)
    {
        return $query->where("featured", true);
    }

    /**
     * Scope to filter available activities.
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeAvailable($query)
    {
        return $query->where("status", "available");
    }
}
