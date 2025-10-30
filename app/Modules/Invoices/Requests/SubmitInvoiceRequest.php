<?php

namespace App\Modules\Invoices\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer'],
            'buyer_id' => ['required', 'integer'],
            'invoice_number' => ['required', 'string', 'max:191'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'due_date' => ['required', 'date', 'after:today'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}


