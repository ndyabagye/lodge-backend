<?php

namespace App\Http\Requests\Admin;

use App\Enums\AccommodationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreAccommodationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStaff();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:accommodations,slug'],
            'type' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string'],
            'short_description' => ['required', 'string', 'max:200'],
            'max_guests' => ['required', 'integer', 'min:1', 'max:50'],
            'num_bedrooms' => ['required', 'integer', 'min:0', 'max:20'],
            'num_bathrooms' => ['required', 'integer', 'min:0', 'max:20'],
            'num_beds' => ['required', 'integer', 'min:0', 'max:50'],
            'size_sqft' => ['nullable', 'integer', 'min:0'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'weekend_price' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
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
            'name.required' => 'Accommodation name is required.',
            'type.required' => 'Accommodation type is required.',
            'description.required' => 'Description is required.',
            'max_guests.required' => 'Maximum number of guests is required.',
            'base_price.required' => 'Base price is required.',
            'weekend_price.required' => 'Weekend price is required.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Add default check-in/out times if not provided
        if (!$this->has('check_in_time')) {
            $this->merge(['check_in_time' => '14:00']);
        }

        if (!$this->has('check_out_time')) {
            $this->merge(['check_out_time' => '11:00']);
        }

        // Default minimum stay
        if (!$this->has('minimum_stay')) {
            $this->merge(['minimum_stay' => 1]);
        }
    }
}
