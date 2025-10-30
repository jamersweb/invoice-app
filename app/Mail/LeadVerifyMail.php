<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeadVerifyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $verifyUrl)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify your email to continue your application',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.leads.verify',
            with: [
                'verifyUrl' => $this->verifyUrl,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
