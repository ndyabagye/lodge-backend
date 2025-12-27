<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
