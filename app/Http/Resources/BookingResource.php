<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "booking_number" => $this->booking_number,
            "check_in_date" => $this->check_in_date->format("Y-m-d"),
            "check_out_date" => $this->check_out_date->format("Y-m-d"),
            "nights" => $this->resource->getNumberOfNights(),
            "num_guests" => $this->num_guests,
            "num_adults" => $this->num_adults,
            "num_children" => $this->num_children,
            "subtotal" => (float) $this->subtotal,
            "tax_amount" => (float) $this->tax_amount,
            "service_fee" => (float) $this->service_fee,
            "cleaning_fee" => (float) $this->cleaning_fee,
            "discount" => (float) $this->discount,
            "total_amount" => (float) $this->total_amount,
            "payment_status" => $this->payment_status,
            "payment_method" => $this->payment_method,
            "status" => $this->status,
            "guest_first_name" => $this->guest_first_name,
            "guest_last_name" => $this->guest_last_name,
            "guest_email" => $this->guest_email,
            "guest_phone" => $this->guest_phone,
            "special_requests" => $this->special_requests,
            "accommodation" => new AccommodationResource(
                $this->whenLoaded("accommodation"),
            ),
            "user" => new UserResource($this->whenLoaded("user")),
            "payments" => PaymentResource::collection(
                $this->whenLoaded("payments"),
            ),
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
