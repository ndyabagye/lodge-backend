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
     * @return array<string,array<int,string>>
     */
    public function rules(): array
    {
        return [
            "start_date" => ["required", "date", "after_or_equal:today"],
            "end_date" => ["required", "date", "after:start_date"],
        ];
    }
}
