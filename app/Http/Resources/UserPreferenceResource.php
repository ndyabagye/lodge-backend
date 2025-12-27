<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPreferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "email_notifications" => $this->email_notifications,
            "sms_notifications" => $this->sms_notifications,
            "marketing_communications" => $this->marketing_communications,
        ];
    }
}
