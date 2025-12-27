<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Amenity extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = ["name", "icon", "category", "order", "active"];

    protected function casts(): array
    {
        return [
            "active" => "boolean",
        ];
    }

    /**
     * Get the accommodations that have this amenity.
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function accommodations(): BelongsToMany
    {
        return $this->belongsToMany(Accommodation::class);
    }

    /**
     * Scope for active amenities.
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive($query)
    {
        return $query->where("active", true)->orderBy("order");
    }
}
