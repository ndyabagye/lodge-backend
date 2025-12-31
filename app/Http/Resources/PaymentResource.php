<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'transaction_id' => $this->transaction_id,
            'reference' => $this->getReference(),
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method,
            'payment_gateway' => [
                'name' => $this->payment_gateway,
                'label' => \App\Enums\PaymentGateway::tryFrom($this->payment_gateway)?->label() ?? $this->payment_gateway,
            ],
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
            ],
            'booking' => new BookingResource($this->whenLoaded('booking')),
            'metadata' => $this->when($request->user()?->isStaff(), $this->metadata),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
