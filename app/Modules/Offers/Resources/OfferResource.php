<?php

namespace App\Modules\Offers\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'invoice_id' => $this->invoice_id,
            'amount' => (float) $this->amount,
            'tenor_days' => $this->tenor_days,
            'discount_rate' => (float) $this->discount_rate,
            'discount_amount' => (float) $this->discount_amount,
            'admin_fee' => (float) $this->admin_fee,
            'net_amount' => (float) $this->net_amount,
            'status' => $this->status,
            'issued_at' => $this->issued_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
        ];
    }
}


