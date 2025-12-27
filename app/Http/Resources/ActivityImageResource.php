<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "url" => $this->url,
            "thumbnail_url" => $this->thumbnail_url,
            "alt_text" => $this->alt_text,
            "caption" => $this->caption,
            "order" => $this->order,
            "is_featured" => $this->is_featured,
        ];
    }
}
