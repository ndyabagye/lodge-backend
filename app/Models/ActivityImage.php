<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityImage extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        "activity_id",
        "url",
        "thumbnail_url",
        "alt_text",
        "caption",
        "order",
        "is_featured",
    ];

    protected function casts(): array
    {
        return [
            "is_featured" => "boolean",
        ];
    }

    /**
     * Get the activity that owns the image.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
