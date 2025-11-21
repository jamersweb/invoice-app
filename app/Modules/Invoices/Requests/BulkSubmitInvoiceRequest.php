<?php

namespace App\Modules\Invoices\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkSubmitInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'invoices' => ['required', 'array', 'min:1', 'max:50'], // Max 50 invoices per bulk submission
            'invoices.*.supplier_id' => ['required', 'integer'],
            'invoices.*.buyer_id' => ['required', 'integer'],
            'invoices.*.invoice_number' => ['required', 'string', 'max:191'],
            'invoices.*.amount' => ['required', 'numeric', 'min:0.01'],
            'invoices.*.currency' => ['required', 'string', 'size:3'],
            'invoices.*.due_date' => ['required', 'date', 'after:today'],
            'invoices.*.file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}

