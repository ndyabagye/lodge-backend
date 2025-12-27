<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccommodationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->isStaff();
    }

    /**
     * @return array<string,array<int,string>>
     */
    public function rules(): array
    {
        $accommodationId = $this->route("accommodation");

        return [
            "name" => ["required", "string", "max:255"],
            "slug" => [
                "required",
                "string",
                "max:255",
                Rule::unique("accommodations")->ignore($accommodationId),
            ],
            "type" => ["required", "string", "max:100"],
            "description" => ["required", "string"],
            "short_description" => ["required", "string", "max:200"],
            "max_guests" => ["required", "integer", "min:1"],
            "num_bedrooms" => ["required", "integer", "min:0"],
            "num_bathrooms" => ["required", "integer", "min:0"],
            "num_beds" => ["required", "integer", "min:1"],
            "size_sqft" => ["nullable", "integer", "min:0"],
            "base_price" => ["required", "numeric", "min:0"],
            "weekend_price" => ["required", "numeric", "min:0"],
            "cleaning_fee" => ["nullable", "numeric", "min:0"],
            "minimum_stay" => ["required", "integer", "min:1"],
            "maximum_stay" => ["nullable", "integer", "min:1"],
            "check_in_time" => ["nullable", "date_format:H:i"],
            "check_out_time" => ["nullable", "date_format:H:i"],
            "status" => [
                "required",
                Rule::in([
                    "available",
                    "maintenance",
                    "coming_soon",
                    "archived",
                ]),
            ],
            "featured" => ["nullable", "boolean"],
            "amenity_ids" => ["nullable", "array"],
            "amenity_ids.*" => ["uuid", "exists:amenities,id"],
        ];
    }
}
