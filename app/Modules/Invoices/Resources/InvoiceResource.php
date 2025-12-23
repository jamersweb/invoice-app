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
            'issue_date' => $this->issue_date?->toDateString(),
            'description' => $this->description,
            'status' => (string) $this->status,
            'ocr_confidence' => $this->ocr_confidence,
            'is_duplicate_flag' => (bool) $this->is_duplicate_flag,
            'file_path' => $this->file_path,
            'bank_account_name' => $this->bank_account_name,
            'bank_name' => $this->bank_name,
            'bank_branch' => $this->bank_branch,
            'bank_iban' => $this->bank_iban,
            'bank_swift' => $this->bank_swift,
            'attachments' => $this->whenLoaded('attachments'),
            'buyer_name' => $this->buyer?->name,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}


