<?php

namespace App\Http\Requests\Booking;

use Illuminate\Foundation\Http\FormRequest;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string,array<int,string>>
     */
    public function rules(): array
    {
        return [
            "accommodation_id" => [
                "required",
                "uuid",
                "exists:accommodations,id",
            ],
            "check_in_date" => ["required", "date", "after_or_equal:today"],
            "check_out_date" => ["required", "date", "after:check_in_date"],
            "num_guests" => ["required", "integer", "min:1"],
            "num_adults" => ["required", "integer", "min:1"],
            "num_children" => ["nullable", "integer", "min:0"],
            "guest_first_name" => [
                "required_without:user_id",
                "string",
                "max:255",
            ],
            "guest_last_name" => [
                "required_without:user_id",
                "string",
                "max:255",
            ],
            "guest_email" => ["required_without:user_id", "email", "max:255"],
            "guest_phone" => ["required_without:user_id", "string", "max:20"],
            "special_requests" => ["nullable", "string", "max:1000"],
            "payment_method" => ["nullable", "string", "max:50"],
        ];
    }

    public function messages(): array
    {
        return [
            "check_in_date.after_or_equal" =>
                "Check-in date must be today or a future date.",
            "check_out_date.after" =>
                "Check-out date must be after check-in date.",
            "guest_first_name.required_without" =>
                "Guest first name is required for guest bookings.",
            "guest_last_name.required_without" =>
                "Guest last name is required for guest bookings.",
            "guest_email.required_without" =>
                "Guest email is required for guest bookings.",
            "guest_phone.required_without" =>
                "Guest phone is required for guest bookings.",
        ];
    }
}
