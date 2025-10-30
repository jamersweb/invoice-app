<?php

namespace App\Modules\Invoices\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'supplier_id' => $this->supplier_id,
            'buyer_id' => $this->buyer_id,
            'invoice_number' => $this->invoice_number,
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'due_date' => $this->due_date?->toDateString(),
            'status' => (string) $this->status,
            'ocr_confidence' => $this->ocr_confidence,
            'is_duplicate_flag' => (bool) $this->is_duplicate_flag,
            'file_path' => $this->file_path,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}


