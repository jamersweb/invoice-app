<?php

namespace App\Mail;

use App\Modules\Invoices\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceSettledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public string $oldStatus,
        public string $newStatus
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invoice Settled - #' . ($this->invoice->invoice_number ?? $this->invoice->id),
        );
    }

    public function content(): Content
    {
        $locale = app()->getLocale();
        $view = $locale === 'ar' ? 'emails.invoice_status_update_ar' : 'emails.invoice_status_update';

        return new Content(
            view: $view,
            with: [
                'invoice' => $this->invoice,
                'oldStatus' => $this->oldStatus,
                'newStatus' => $this->newStatus,
                'statusLabel' => 'Settled',
                'statusColor' => 'green',
                'statusIcon' => '🤝',
            ]
        );
    }
}
