<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Amenity> $amenities
 * @property-read int|null $amenities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\BlockedDate> $blockedDates
 * @property-read int|null $blocked_dates_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $bookings
 * @property-read int|null $bookings_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccommodationImage> $featuredImage
 * @property-read int|null $featured_image_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\AccommodationImage> $images
 * @property-read int|null $images_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review> $reviews
 * @property-read int|null $reviews_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Accommodation available()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Accommodation featured()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Accommodation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Accommodation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Accommodation onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Accommodation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Accommodation withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Accommodation withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAccommodation {}
}

namespace App\Models{
/**
 * @property-read \App\Models\Accommodation|null $accommodation
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccommodationImage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccommodationImage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AccommodationImage query()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAccommodationImage {}
}

namespace App\Models{
/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityImage> $featuredImage
 * @property-read int|null $featured_image_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityImage> $images
 * @property-read int|null $images_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review> $reviews
 * @property-read int|null $reviews_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity available()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity featured()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Activity withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperActivity {}
}

namespace App\Models{
/**
 * @property-read \App\Models\Activity|null $activity
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityImage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityImage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityImage query()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperActivityImage {}
}

namespace App\Models{
/**
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLog query()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperActivityLog {}
}

namespace App\Models{
/**
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Accommodation> $accommodations
 * @property-read int|null $accommodations_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Amenity active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Amenity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Amenity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Amenity query()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAmenity {}
}

namespace App\Models{
/**
 * @property-read \App\Models\Accommodation|null $accommodation
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlockedDate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlockedDate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BlockedDate query()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperBlockedDate {}
}

namespace App\Models{
/**
 * @property-read \App\Models\Accommodation|null $accommodation
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \App\Models\Review|null $review
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking confirmed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking upcoming()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Booking withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperBooking {}
}

namespace App\Models{
/**
 * @property-read \App\Models\Booking|null $booking
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment query()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperPayment {}
}

namespace App\Models{
/**
 * @property-read \App\Models\Accommodation|null $accommodation
 * @property-read \App\Models\Activity|null $activity
 * @property-read \App\Models\Booking|null $booking
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review approved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperReview {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Booking> $bookings
 * @property-read int|null $bookings_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \App\Models\UserPreference|null $preferences
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review> $reviews
 * @property-read int|null $reviews_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUser {}
}

namespace App\Models{
/**
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPreference newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPreference newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserPreference query()
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUserPreference {}
}

