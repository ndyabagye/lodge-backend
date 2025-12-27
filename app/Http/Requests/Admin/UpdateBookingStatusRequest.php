<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingStatusRequest extends FormRequest
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
        return [
            "status" => [
                "required",
                Rule::in([
                    "pending",
                    "confirmed",
                    "checked_in",
                    "checked_out",
                    "cancelled",
                ]),
            ],
            "internal_notes" => ["nullable", "string"],
        ];
    }
}
