<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccommodationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "slug" => $this->slug,
            "type" => $this->type,
            "description" => $this->description,
            "short_description" => $this->short_description,

            "max_guests" => $this->max_guests,
            "num_bedrooms" => $this->num_bedrooms,
            "num_bathrooms" => $this->num_bathrooms,
            "num_beds" => $this->num_beds,
            "size_sqft" => $this->size_sqft,

            "base_price" => (float) $this->base_price,
            "weekend_price" => (float) $this->weekend_price,
            "cleaning_fee" => (float) $this->cleaning_fee,

            "minimum_stay" => $this->minimum_stay,
            "maximum_stay" => $this->maximum_stay,

            "check_in_time" => $this->check_in_time?->format("H:i"),
            "check_out_time" => $this->check_out_time?->format("H:i"),

            "status" => $this->status,
            "featured" => $this->featured,
            "rating" => (float) $this->rating,
            "views" => $this->views,

            "bookings" => $this->bookings,

            "images" => AccommodationImageResource::collection(
                $this->whenLoaded("images"),
            ),

            "featured_image" => $this->whenLoaded("featuredImage", function () {
                return $this->featuredImage->first()
                    ? new AccommodationImageResource(
                        $this->featuredImage->first(),
                    )
                    : null;
            }),
            "amenities" => AmenityResource::collection(
                $this->whenLoaded("amenities"),
            ),
            
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
