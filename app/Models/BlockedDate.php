<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperBlockedDate
 */
class BlockedDate extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        "accommodation_id",
        "start_date",
        "end_date",
        "reason",
        "notes",
    ];

    protected function casts(): array
    {
        return [
            "start_date" => "date",
            "end_date" => "date",
        ];
    }

    /**
     * Get the accommodation that owns the blocked date.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function accommodation(): BelongsTo
    {
        return $this->belongsTo(Accommodation::class);
    }
}
