<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperActivityLog
 */
class ActivityLog extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        "user_id",
        "action",
        "model_type",
        "model_id",
        "description",
        "changes",
        "ip_address",
        "user_agent",
    ];

    protected function casts(): array
    {
        return [
            "changes" => "array",
        ];
    }

    /**
     * Get the user who performed the action.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
