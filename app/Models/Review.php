<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin IdeHelperReview
 */
class Review extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        "booking_id",
        "accommodation_id",
        "activity_id",
        "user_id",
        "rating",
        "title",
        "comment",
        "status",
    ];

    /**
     * Get the booking that owns the review.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the accommodation that owns the review.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

    /**
     * Get the activity that owns the review.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Get the user that owns the review.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the reviews that are approved.
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeApproved($query)
    {
        return $query->where("status", "approved");
    }
}
