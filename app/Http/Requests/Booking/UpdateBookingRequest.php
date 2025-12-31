<?php

namespace App\Http\Requests\Booking;

use App\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $booking = $this->route('booking');
        return $this->user()->id === $booking->user_id || $this->user()->isStaff();
    }

    public function rules(): array
    {
        return [
            'check_in_date' => ['sometimes', 'date', 'after_or_equal:today'],
            'check_out_date' => ['sometimes', 'date', 'after:check_in_date'],
            'num_guests' => ['sometimes', 'integer', 'min:1'],
            'num_adults' => ['sometimes', 'integer', 'min:1'],
            'num_children' => ['sometimes', 'integer', 'min:0'],
            'special_requests' => ['nullable', 'string', 'max:1000'],
            'internal_notes' => ['sometimes', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'check_in_date.after_or_equal' => 'Check-in date must be today or a future date.',
            'check_out_date.after' => 'Check-out date must be after check-in date.',
            'num_guests.min' => 'At least one guest is required.',
        ];
    }
}
