<?php

namespace App\Mail;

use App\Modules\Invoices\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RepaymentScheduleCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public int $parts
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Repayment Schedule Created - Invoice #' . ($this->invoice->invoice_number ?? $this->invoice->id),
        );
    }

    public function content(): Content
    {
        $locale = app()->getLocale();
        $view = $locale === 'ar' ? 'emails.repayment_schedule_created_ar' : 'emails.repayment_schedule_created';

        return new Content(
            view: $view,
            with: [
                'invoice' => $this->invoice,
                'parts' => $this->parts,
            ]
        );
    }
}
