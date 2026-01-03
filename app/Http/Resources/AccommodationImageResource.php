<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\URL;

class AccommodationImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            // Always return absolute URLs
            // "url" => $this->url,
            // "thumbnail_url" => $this->thumbnail_url,
            "url" => $this->url
                ? URL::to($this->url)
                : null,

            "thumbnail_url" => $this->thumbnail_url
                ? URL::to($this->thumbnail_url)
                : null,

            "alt_text" => $this->alt_text,
            "caption" => $this->caption,
            "order" => $this->order,
            "is_featured" => $this->is_featured,
        ];
    }
}
