<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin();
    }

    /**
     * @return array<string,array<int,string>>
     */
    public function rules(): array
    {
        return [
            "role" => ["required", Rule::in(["guest", "staff", "admin"])],
        ];
    }
}
