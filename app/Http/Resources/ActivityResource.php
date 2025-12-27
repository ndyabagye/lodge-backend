<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => $this->name,
            "slug" => $this->slug,
            "category" => $this->category,
            "description" => $this->description,
            "short_description" => $this->short_description,
            "duration" => $this->duration,
            "price" => $this->when($this->price, (float) $this->price),
            "adult_price" => $this->when(
                $this->adult_price,
                (float) $this->adult_price,
            ),
            "child_price" => $this->when(
                $this->child_price,
                (float) $this->child_price,
            ),
            "group_price" => $this->when(
                $this->group_price,
                (float) $this->group_price,
            ),
            "max_participants" => $this->max_participants,
            "min_age" => $this->min_age,
            "max_age" => $this->max_age,
            "requirements" => $this->requirements,
            "safety_info" => $this->safety_info,
            "included" => $this->included,
            "excluded" => $this->excluded,
            "status" => $this->status,
            "featured" => $this->featured,
            "rating" => (float) $this->rating,
            "images" => ActivityImageResource::collection(
                $this->whenLoaded("images"),
            ),
            "featured_image" => $this->whenLoaded("featuredImage", function () {
                return $this->featuredImage->first()
                    ? new ActivityImageResource($this->featuredImage->first())
                    : null;
            }),
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
