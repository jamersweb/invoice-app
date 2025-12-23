<?php

namespace App\Modules\Invoices\Services;

use App\Modules\Invoices\Models\Invoice;
use App\Modules\Invoices\Events\InvoiceSubmitted;
use App\Modules\Invoices\Jobs\ProcessInvoiceOcr;
use App\Modules\Invoices\States\Draft;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\AuditEvent;

class InvoiceSubmissionService
{
    public function submit(array $data): Invoice
    {
        return DB::transaction(function () use ($data) {
            $path = null;
            $files = $data['files'] ?? [];
            if (!is_array($files) && isset($data['file'])) {
                $files = [$data['file']];
            }

            if (!empty($files)) {
                $path = Storage::disk('public')->putFile('invoices', $files[0]);
            }

            $toleranceLow = (float) $data['amount'] * 0.95;
            $toleranceHigh = (float) $data['amount'] * 1.05;
            $isDuplicate = false;
            if (isset($data['buyer_id'])) {
                $isDuplicate = Invoice::query()
                    ->where('buyer_id', $data['buyer_id'])
                    ->where('invoice_number', $data['invoice_number'])
                    ->whereBetween('amount', [$toleranceLow, $toleranceHigh])
                    ->exists();
            }

            $payload = $data;
            $payload['file_path'] = $path;
            $payload['is_duplicate_flag'] = $isDuplicate;
            $payload['status'] = 'under_review';

            /** @var Invoice $invoice */
            $invoice = Invoice::create($payload);

            foreach ($files as $file) {
                // Skip the first one if we already saved it manually for the legacy column, 
                // but actually it's better to save ALL in attachments.
                $filePath = Storage::disk('public')->putFile('invoices', $file);
                $invoice->attachments()->create([
                    'file_path' => $filePath,
                    'file_name' => $file->getClientOriginalName(),
                ]);
            }

            AuditEvent::create([
                'actor_type' => 'user',
                'actor_id' => auth()->id(),
                'entity_type' => Invoice::class,
                'entity_id' => $invoice->id,
                'action' => 'invoice_submitted',
                'diff_json' => [
                    'new' => [
                        'supplier_id' => $invoice->supplier_id,
                        'buyer_id' => $invoice->buyer_id,
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => $invoice->amount,
                        'currency' => $invoice->currency,
                        'due_date' => $invoice->due_date?->toDateString(),
                        'is_duplicate_flag' => $invoice->is_duplicate_flag,
                    ],
                ],
                'ip' => request()->ip(),
                'ua' => request()->userAgent(),
            ]);

            event(new InvoiceSubmitted($invoice));
            if (config('features.auto_ocr', true)) {
                ProcessInvoiceOcr::dispatch($invoice);
            }
            return $invoice;
        });
    }
}


