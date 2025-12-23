<?php

namespace App\Mail;

use App\Models\Agreement;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContractSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Agreement $agreement
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Agreement for Review - Action Required',
        );
    }

    public function content(): Content
    {
        $locale = app()->getLocale();
        $view = $locale === 'ar' ? 'emails.contract_sent_ar' : 'emails.contract_sent';

        return new Content(
            view: $view,
            with: [
                'agreement' => $this->agreement,
                'supplierName' => $this->agreement->invoice?->supplier?->company_name ?? 'Valued Customer',
            ]
        );
    }
}
