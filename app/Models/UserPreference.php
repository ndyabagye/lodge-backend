<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperUserPreference
 */
class UserPreference extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        "user_id",
        "email_notifications",
        "sms_notifications",
        "marketing_communications",
    ];

    protected function casts(): array
    {
        return [
            "email_notifications" => "boolean",
            "sms_notifications" => "boolean",
            "marketing_communications" => "boolean",
        ];
    }

    /**
     * Get the user that owns the UserPreference
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
