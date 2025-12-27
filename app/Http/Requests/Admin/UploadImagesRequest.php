<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UploadImagesRequest extends FormRequest
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
            "images" => ["required", "array", "min:1"],
            "images.*" => [
                "required",
                "image",
                "mimes:jpeg,png,jpg,webp",
                "max:5120",
            ], // 5MB
            "alt_texts" => ["nullable", "array"],
            "alt_texts.*" => ["nullable", "string", "max:255"],
            "captions" => ["nullable", "array"],
            "captions.*" => ["nullable", "string", "max:255"],
        ];
    }

    public function messages(): array
    {
        return [
            "images.*.image" => "Each file must be an image.",
            "images.*.mimes" =>
                "Images must be in JPEG, PNG, JPG, or WebP format.",
            "images.*.max" => "Each image must not exceed 5MB.",
        ];
    }
}
