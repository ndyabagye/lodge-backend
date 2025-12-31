<?php

namespace App\Http\Requests\Admin;

use App\Enums\ActivityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStaff();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:activities,slug'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['required', 'string'],
            'short_description' => ['required', 'string', 'max:200'],
            'duration' => ['nullable', 'integer', 'min:0', 'max:1440'], // Max 24 hours in minutes
            'price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'adult_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'child_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'group_price' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'max_participants' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'min_age' => ['nullable', 'integer', 'min:0', 'max:100'],
            'max_age' => ['nullable', 'integer', 'min:0', 'max:100', 'gte:min_age'],
            'requirements' => ['nullable', 'string'],
            'safety_info' => ['nullable', 'string'],
            'included' => ['nullable', 'string'],
            'excluded' => ['nullable', 'string'],
            'status' => ['sometimes', new Enum(ActivityStatus::class)],
            'featured' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Activity name is required.',
            'description.required' => 'Description is required.',
            'short_description.required' => 'Short description is required.',
            'max_age.gte' => 'Maximum age must be greater than or equal to minimum age.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // If no individual prices set, ensure at least base price exists
        if (!$this->has('adult_price') && !$this->has('child_price') && !$this->has('group_price')) {
            if (!$this->has('price')) {
                $this->merge(['price' => 0]);
            }
        }
    }
}
