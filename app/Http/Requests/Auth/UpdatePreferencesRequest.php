<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePreferencesRequest extends FormRequest
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
            "email_notifications" => ["sometimes", "boolean"],
            "sms_notifications" => ["sometimes", "boolean"],
            "marketing_communications" => ["sometimes", "boolean"],
        ];
    }
}
