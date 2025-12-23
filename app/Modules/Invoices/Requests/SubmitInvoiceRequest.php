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
            'supplier_id' => ['nullable', 'integer'],
            'buyer_id' => ['nullable', 'integer'],
            'invoice_number' => ['required', 'string', 'max:191'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'due_date' => ['required', 'date', 'after:today'],
            'issue_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'bank_account_name' => ['nullable', 'string', 'max:191'],
            'bank_name' => ['nullable', 'string', 'max:191'],
            'bank_branch' => ['nullable', 'string', 'max:191'],
            'bank_iban' => ['nullable', 'string', 'max:191'],
            'bank_swift' => ['nullable', 'string', 'max:191'],
        ];
    }
}


