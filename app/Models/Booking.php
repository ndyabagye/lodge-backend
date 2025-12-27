<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin IdeHelperBooking
 */
class Booking extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        "booking_number",
        "user_id",
        "accommodation_id",
        "check_in_date",
        "check_out_date",
        "num_guests",
        "num_adults",
        "num_children",
        "subtotal",
        "tax_amount",
        "service_fee",
        "cleaning_fee",
        "discount",
        "total_amount",
        "payment_status",
        "payment_method",
        "status",
        "guest_first_name",
        "guest_last_name",
        "guest_email",
        "guest_phone",
        "special_requests",
        "internal_notes",
    ];

    protected function casts(): array
    {
        return [
            "check_in_date" => "date",
            "check_out_date" => "date",
            "subtotal" => "decimal:2",
            "tax_amount" => "decimal:2",
            "service_fee" => "decimal:2",
            "cleaning_fee" => "decimal:2",
            "discount" => "decimal:2",
            "total_amount" => "decimal:2",
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->booking_number)) {
                $booking->booking_number = self::generateBookingNumber();
            }
        });
    }

    public static function generateBookingNumber(): string
    {
        do {
            $number = "BK" . date("Ymd") . strtoupper(substr(uniqid(), -6));
        } while (self::query()->where("booking_number", $number)->exists());

        return $number;
    }

    /**
     * Get the user associated with the booking.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the accommodation associated with the booking.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }

    /**
     * Get the payments associated with the booking.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get the review associated with the booking.
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /**
     * Get the number of nights for the booking.
     * @return int
     */
    public function getNumberOfNights(): int
    {
        return $this->check_in_date->diffInDays($this->check_out_date);
    }

    /**
     * Check if the booking is upcoming.
     * @return bool
     */
    public function isUpcoming(): bool
    {
        return $this->check_in_date->isFuture();
    }

    /**
     * Check if the booking is past.
     * @return bool
     */
    public function isPast(): bool
    {
        return $this->check_out_date->isPast();
    }

    /**
     * Check if the booking is active.
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->check_in_date->isPast() &&
            $this->check_out_date->isFuture();
    }

    /**
     * Check if the booking can be cancelled.
     * @return bool
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ["pending", "confirmed"]) &&
            $this->check_in_date->isFuture();
    }

    /**
     * Scope for pending bookings.
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePending($query)
    {
        return $query->where("status", "pending");
    }

    /**
     * Scope for confirmed bookings.
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeConfirmed($query)
    {
        return $query->where("status", "confirmed");
    }

    /**
     * Scope for upcoming bookings.
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeUpcoming($query)
    {
        return $query->where("check_in_date", ">", now());
    }
}
