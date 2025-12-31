<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperPayment
 */
class Payment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        "booking_id",
        "transaction_id",
        "amount",
        "currency",
        "payment_method",
        "payment_gateway",
        "status",
        "metadata",
    ];

    protected function casts(): array
    {
        return [
            "amount" => "decimal:2",
            'status' => PaymentStatus::class,
            "metadata" => "array",
        ];
    }

    /**
     * Get the booking that owns the payment.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Check if payment is completed
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === PaymentStatus::COMPLETED;
    }

    /**
     * Check if payment is pending
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    /**
     * Check if payment has failed
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    /**
     * Check if payment is refunded
     * @return bool
     */
    public function isRefunded(): bool
    {
        return $this->status === PaymentStatus::REFUNDED;
    }

    /**
     * Get reference from metadata
     * @return string|null
     */
    public function getReference(): ?string
    {
        return $this->metadata['reference'] ?? null;
    }

    /**
     * Get gateway response from metadata
     * @return array|null
     */
    public function getGatewayResponse(): ?array
    {
        return $this->metadata['gateway_response'] ?? null;
    }

    /**
     * Scope: Completed payments
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', PaymentStatus::COMPLETED);
    }

    /**
     * Scope: Pending payments
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::PENDING);
    }

    /**
     * Scope: Failed payments
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('status', PaymentStatus::FAILED);
    }
}
