<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "transaction_id" => $this->transaction_id,
            "amount" => (float) $this->amount,
            "currency" => $this->currency,
            "payment_method" => $this->payment_method,
            "payment_gateway" => $this->payment_gateway,
            "status" => $this->status,
            "metadata" => $this->metadata,
            "created_at" => $this->created_at,
        ];
    }
}
