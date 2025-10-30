<?php

namespace App\Modules\Invoices\Jobs;

use App\Modules\Invoices\Models\Invoice;
use App\Modules\Invoices\Contracts\OcrServiceInterface;
use App\Models\AuditEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessInvoiceOcr implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public Invoice $invoice)
    {
    }

    public function handle(OcrServiceInterface $ocr): void
    {
        if (!$this->invoice->file_path) {
            return;
        }
        $result = $ocr->extract($this->invoice->file_path);
        $this->invoice->ocr_data = $result;
        $this->invoice->ocr_confidence = (int) ($result['confidence_score'] ?? 0);
        if ($this->invoice->ocr_confidence < 80) {
            $this->invoice->status = 'under_review';
        }
        $this->invoice->save();

        AuditEvent::create([
            'actor_type' => 'system',
            'actor_id' => null,
            'entity_type' => Invoice::class,
            'entity_id' => $this->invoice->id,
            'action' => 'invoice_ocr_processed',
            'diff_json' => [
                'new' => [
                    'ocr_confidence' => $this->invoice->ocr_confidence,
                    'status' => $this->invoice->status,
                ],
            ],
            'ip' => null,
            'ua' => 'queue:process-invoice-ocr',
        ]);
    }
}


