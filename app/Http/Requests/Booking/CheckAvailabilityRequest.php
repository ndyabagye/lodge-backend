<?php
namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CheckAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'accommodation_id' => ['required', 'uuid', 'exists:accommodations,id'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'accommodation_id.required' => 'Please select an accommodation.',
            'accommodation_id.exists' => 'The selected accommodation does not exist.',
            'start_date.required' => 'Check-in date is required.',
            'start_date.after_or_equal' => 'Check-in date cannot be in the past.',
            'end_date.required' => 'Check-out date is required.',
            'end_date.after' => 'Check-out date must be after check-in date.',
        ];
    }
}
