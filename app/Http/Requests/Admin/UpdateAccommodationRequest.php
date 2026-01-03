<?php

namespace App\Http\Requests\Admin;

use App\Enums\AccommodationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateAccommodationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() || $this->user()?->isStaff();
    }

    public function rules(): array
    {
        $accommodation = $this->route('accommodation');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('accommodations', 'slug')->ignore($accommodation->id)],
            'type' => ['sometimes', 'string', 'max:50'],
            'description' => ['sometimes', 'string'],
            'short_description' => ['sometimes', 'string', 'max:200'],
            'max_guests' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'num_bedrooms' => ['sometimes', 'integer', 'min:0', 'max:20'],
            'num_bathrooms' => ['sometimes', 'integer', 'min:0', 'max:20'],
            'num_beds' => ['sometimes', 'integer', 'min:0', 'max:50'],
            'size_sqft' => ['nullable', 'integer', 'min:0'],
            'base_price' => ['sometimes', 'numeric', 'min:0', 'max:999999999.99'],
            'weekend_price' => ['sometimes', 'numeric', 'min:0', 'max:999999999.99'],
            'cleaning_fee' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'minimum_stay' => ['nullable', 'integer', 'min:1', 'max:365'],
            'maximum_stay' => ['nullable', 'integer', 'min:1', 'max:365'],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'status' => ['sometimes', new Enum(AccommodationStatus::class)],
            'featured' => ['sometimes', 'boolean'],
            'amenity_ids' => ['nullable', 'array'],
            'amenity_ids.*' => ['uuid', 'exists:amenities,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'max_guests.min' => 'At least one guest must be allowed.',
            'base_price.min' => 'Base price must be greater than zero.',
            'weekend_price.min' => 'Weekend price must be greater than zero.',
        ];
    }
}
