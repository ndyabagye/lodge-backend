<?php

namespace App\Http\Requests\Admin;

use App\Enums\ActivityStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isStaff();
    }

    public function rules(): array
    {
        $activity = $this->route('activity');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', Rule::unique('activities', 'slug')->ignore($activity->id)],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['sometimes', 'string'],
            'short_description' => ['sometimes', 'string', 'max:200'],
            'duration' => ['nullable', 'integer', 'min:0', 'max:1440'],
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
            'max_age.gte' => 'Maximum age must be greater than or equal to minimum age.',
        ];
    }
}
