<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
            "first_name" => ["sometimes", "string", "max:255"],
            "last_name" => ["sometimes", "string", "max:255"],
            "email" => [
                "sometimes",
                "string",
                "email",
                "max:255",
                Rule::unique("users")->ignore($this->user()->id),
            ],
            "phone" => ["nullable", "string", "max:20"],
        ];
    }
}
