<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityRequest extends FormRequest
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
        $activityId = $this->route("activity");

        return [
            "name" => ["required", "string", "max:255"],
            "slug" => [
                "required",
                "string",
                "max:255",
                Rule::unique("activities")->ignore($activityId),
            ],
            "category" => ["required", "string", "max:100"],
            "description" => ["required", "string"],
            "short_description" => ["required", "string", "max:200"],
            "duration" => ["nullable", "integer", "min:0"],
            "price" => ["nullable", "numeric", "min:0"],
            "adult_price" => ["nullable", "numeric", "min:0"],
            "child_price" => ["nullable", "numeric", "min:0"],
            "group_price" => ["nullable", "numeric", "min:0"],
            "max_participants" => ["nullable", "integer", "min:1"],
            "min_age" => ["nullable", "integer", "min:0"],
            "max_age" => ["nullable", "integer", "min:0"],
            "requirements" => ["nullable", "string"],
            "safety_info" => ["nullable", "string"],
            "included" => ["nullable", "string"],
            "excluded" => ["nullable", "string"],
            "status" => [
                "required",
                Rule::in(["available", "unavailable", "coming_soon"]),
            ],
            "featured" => ["nullable", "boolean"],
        ];
    }
}
