<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        "email",
        "password",
        "first_name",
        "last_name",
        "phone",
        "role",
        "email_verified_at",
        "status",
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = ["password", "remember_token"];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "email_verified_at" => "datetime",
            "password" => "hashed",
        ];
    }

    /**
     * Get the user's preferences.
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function preferences(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    /**
     * Get the user's bookings.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Get the user's reviews.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * User is admin
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === "admin";
    }

    /**
     * User is staff
     * @return bool
     */
    public function isStaff(): bool
    {
        return $this->role === "staff";
    }

    /**
     * User is guest
     * @return bool
     */
    public function isGuest(): bool
    {
        return $this->role === "guest";
    }

    /**
     * User is active
     */
    public function isActive(): bool
    {
        return $this->status === "active";
    }
}
