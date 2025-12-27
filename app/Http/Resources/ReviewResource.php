<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "rating" => $this->rating,
            "title" => $this->title,
            "comment" => $this->comment,
            "status" => $this->status,
            "user" => new UserResource($this->whenLoaded("user")),
            "created_at" => $this->created_at,
        ];
    }
}
